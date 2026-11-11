<?php

namespace App\Services;

use App\Exceptions\OrderPaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirmation d'un paiement (indépendante de tout fournisseur : paiement manuel confirmé par l'admin).
 * Seul endroit qui fait passer une commande à `paid`.
 */
class OrderPaymentService
{
    /**
     * Confirme le paiement d'une commande.
     *
     * - idempotent : déjà `paid` => retourne la commande sans rien refaire ;
     * - pending/processing => paid (billets vendus, expires_at nul, trace Payment) ;
     * - failed (stock déjà libéré) => re-réservation du stock sous verrou, sinon exception métier
     *   (jamais de paiement silencieusement perdu : remboursement/relance manuel) ;
     * - refunded (ou autre statut) => refusé.
     *
     * L'email de confirmation part APRÈS le commit ; son échec n'annule pas la confirmation.
     *
     * @throws OrderPaymentException
     */
    public function confirmPayment(Order $order, ?string $reference = null, ?User $confirmedBy = null): Order
    {
        $confirmedNow = false;

        $locked = DB::transaction(function () use ($order, $reference, $confirmedBy, &$confirmedNow) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === 'paid') {
                return $locked; // idempotence
            }

            if ($locked->payment_status === 'failed') {
                if (!$locked->reclaimStock()) {
                    Log::critical('Paiement à confirmer sur commande libérée, stock indisponible : remboursement/relance manuel requis', [
                        'order_id' => $locked->id,
                        'order_number' => $locked->order_number,
                        'confirmed_by' => $confirmedBy?->id,
                    ]);
                    throw new OrderPaymentException(
                        'Stock indisponible : la commande avait expiré et les billets ne sont plus disponibles. Remboursement ou relance manuelle de l\'acheteur nécessaire.'
                    );
                }
            } elseif (!in_array($locked->payment_status, ['pending', 'processing'], true)) {
                throw new OrderPaymentException(
                    'Cette commande ne peut pas être confirmée (statut : ' . $locked->payment_status . ').'
                );
            }

            $reference = $reference !== null && trim($reference) !== '' ? trim($reference) : null;

            $locked->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'expires_at' => null,
                'payment_reference' => $reference ?? $locked->payment_reference,
            ]);

            // Billets émis (vendus) uniquement après paiement confirmé
            foreach ($locked->tickets as $ticket) {
                $ticket->markAsSold();
            }

            // Une seule commission par commande : créée à la commande ; ici seulement si absente
            $locked->unsetRelation('commission');
            $locked->createCommission();

            // Trace : qui a confirmé, quand, quelle référence
            Payment::create([
                'order_id' => $locked->id,
                'payment_method' => $locked->payment_method ?: 'manual',
                'amount' => $locked->total_amount,
                'status' => 'completed',
                'provider_transaction_id' => $reference,
                'provider_response' => [
                    'confirmed_by' => $confirmedBy?->id,
                    'confirmed_at' => now()->toIso8601String(),
                    'manual' => true,
                ],
                'completed_at' => now(),
            ]);

            Log::info('Paiement confirmé', [
                'order_id' => $locked->id,
                'order_number' => $locked->order_number,
                'amount' => $locked->total_amount,
                'confirmed_by' => $confirmedBy?->id,
            ]);

            $confirmedNow = true;

            return $locked;
        });

        if ($confirmedNow) {
            try {
                app(EmailService::class)->sendPaymentConfirmation($locked);
            } catch (\Throwable $e) {
                Log::error('Échec envoi email de confirmation de paiement (paiement confirmé malgré tout)', [
                    'order_id' => $locked->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $order->refresh();

        return $order;
    }
}
