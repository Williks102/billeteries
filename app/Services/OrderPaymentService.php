<?php

namespace App\Services;

use App\Exceptions\OrderPaymentException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
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

                // I1 : fail() avait annulé la commission ; la vente est reconfirmée, la même commission
                // (une seule par commande) redevient pending. Aucune nouvelle commission n'est créée.
                Commission::where('order_id', $locked->id)->where('status', 'cancelled')->update(['status' => 'pending']);
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

    /**
     * Changement de statut demandé par l'admin : seul point d'entrée des voies admin (M1/M2).
     * Applique la table Order::STATUS_TRANSITIONS ; le stock reste cohérent dans tous les cas.
     *
     * @throws OrderPaymentException transition interdite ou état modifié entre-temps
     */
    public function changeStatus(Order $order, string $to, ?string $notes = null, ?User $by = null): Order
    {
        $order->refresh();
        $from = $order->payment_status;

        if ($from !== $to) {
            if (!Order::canTransition($from, $to)) {
                throw new OrderPaymentException(sprintf(
                    'Transition de statut interdite : « %s » vers « %s ».',
                    $from,
                    $to
                ));
            }

            match ($to) {
                'paid' => $this->confirmPayment($order, null, $by),
                'failed' => $this->fail($order),
                'refunded' => $this->refund($order),
            };

            Log::info('Statut de commande modifié par admin', [
                'order_id' => $order->id,
                'from' => $from,
                'to' => $to,
                'admin_id' => $by?->id,
            ]);
        }

        if ($notes !== null) {
            $order->update(['admin_notes' => $notes]);
        }

        return $order->refresh();
    }

    /**
     * pending/processing -> failed : libère le stock (releaseStock : verrou, quantity_sold, billets annulés)
     * et annule la commission.
     */
    public function fail(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            if (!$order->releaseStock()) {
                throw new OrderPaymentException('Le statut de la commande a changé entre-temps : opération annulée.');
            }

            Commission::where('order_id', $order->id)->where('status', 'pending')->update(['status' => 'cancelled']);
        });

        return $order->refresh();
    }

    /**
     * paid -> refunded : verrou sur la commande puis les types de billets, billets annulés,
     * stock libéré (les places redeviennent vendables), commission suspendue.
     */
    public function refund(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== 'paid') {
                throw new OrderPaymentException('Seules les commandes payées peuvent être remboursées.');
            }

            foreach ($locked->orderItems()->orderBy('ticket_type_id')->get() as $item) {
                TicketType::whereKey($item->ticket_type_id)->lockForUpdate()->first()?->releaseTickets($item->quantity);
            }

            $ticketIds = DB::table('order_tickets')->where('order_id', $locked->id)->pluck('ticket_id');
            Ticket::whereIn('id', $ticketIds)->update(['status' => 'cancelled']);

            Commission::where('order_id', $locked->id)->whereIn('status', ['pending'])->update(['status' => 'held']);

            $locked->update(['payment_status' => 'refunded']);
        });

        return $order->refresh();
    }
}
