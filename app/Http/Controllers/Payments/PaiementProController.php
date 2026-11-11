<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaiementProService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaiementProController extends Controller
{
    private PaiementProService $paiementProService;
    private EmailService $emailService;

    public function __construct(PaiementProService $paiementProService, EmailService $emailService)
    {
        $this->paiementProService = $paiementProService;
        $this->emailService = $emailService;
    }

    /**
     * Initier le paiement PaiementPro
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'channel' => 'nullable|in:CARD,MOMO,OMCIV2,FLOOZ,PAYPAL,WAVECI'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Récupérer la commande
            $order = Order::with(['user', 'event'])->findOrFail($request->order_id);

            // Vérifier que la commande peut être payée
            if ($order->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être payée'
                ], 400);
            }

            // Vérifier que l'utilisateur est propriétaire de la commande
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            // Initier la transaction
            $result = $this->paiementProService->initTransaction($order, [
                'channel' => $request->channel
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'sessionId' => $result['sessionId'],
                    'redirectUrl' => $result['redirectUrl'],
                    'message' => 'Transaction initiée avec succès'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'code' => $result['code']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement PaiementPro', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initiation du paiement'
            ], 500);
    }
}

    /**
     * Traiter la notification de PaiementPro
     */
    public function notification(Request $request)
    {
        // Pas de log du corps complet (hashcode, PII)
        Log::info('Réception notification PaiementPro', [
            'referenceNumber' => $request->input('referenceNumber'),
            'responsecode' => $request->input('responsecode'),
        ]);

        $validator = Validator::make($request->all(), [
            'merchantId' => 'required',
            'referenceNumber' => 'required',
            'amount' => 'required|numeric',
            'responsecode' => 'required',
            'transactiondt' => 'required',
            'hashcode' => 'required',
        ]);

        if ($validator->fails()) {
            Log::warning('Notification PaiementPro invalide', [
                'fields' => array_keys($validator->errors()->toArray()),
            ]);

            return response('Invalid parameters', 400);
        }

        try {
            $result = $this->paiementProService->handleNotification($request->all());

            if ($result['success']) {
                // Emails une seule fois : uniquement au passage effectif à paid (pas sur rejeu)
                if (!empty($result['payment_successful']) && isset($result['order'])) {
                    $this->sendConfirmationEmails($result['order']);
                }

                return response('OK', 200);
            }

            Log::error('Échec traitement notification PaiementPro', ['error' => $result['error'] ?? 'unknown']);
            return response('Error: ' . ($result['error'] ?? 'Unknown error'), 400);

        } catch (\Exception $e) {
            Log::error('Exception notification PaiementPro', ['error' => $e->getMessage()]);

            return response('Internal error', 500);
        }
    }

    /**
     * Page de retour après paiement. Ne modifie rien : le message est basé sur le statut
     * réel de la commande (mis à jour par le webhook signé), jamais sur les paramètres GET.
     */
    public function return(Request $request)
    {
        try {
            $context = [];
            if ($request->has('returnContext')) {
                parse_str((string) $request->returnContext, $context);
            }

            $order = null;
            if (isset($context['order_id'])) {
                $order = Order::with('user')->find($context['order_id']);
            } elseif ($request->has('referenceNumber')) {
                $order = Order::with('user')->where('order_number', $request->referenceNumber)->first();
            }

            if (!$order) {
                return redirect()->route('home');
            }

            [$flash, $message] = match ($order->payment_status) {
                'paid' => ['success', 'Paiement réussi !'],
                'failed', 'cancelled' => ['error', 'Paiement échoué ou annulé'],
                default => ['warning', 'Paiement en cours de traitement : votre commande sera confirmée dès réception de la confirmation.'],
            };

            if ($order->user && !$order->user->is_guest) {
                return redirect()->route('acheteur.orders.show', $order)->with($flash, $message);
            }

            if ($order->guest_token) {
                return redirect()->route('checkout.guest.confirmation', $order->guest_token)->with($flash, $message);
            }

            return redirect()->route('home')->with($flash, $message);

        } catch (\Exception $e) {
            Log::error('Erreur page retour PaiementPro', ['error' => $e->getMessage()]);

            return redirect()->route('home')
                           ->with('error', 'Une erreur est survenue lors du retour de paiement');
        }
    }

    /**
     * Annuler un paiement : uniquement une commande pending/processing, par son propriétaire
     * (authentifié) ou via son guest_token. Libère le stock.
     */
    public function cancel(Request $request)
    {
        try {
            $order = Order::find($request->query('order_id'));

            if ($order && $this->canCancel($request, $order)) {
                $order->releaseStock();

                if ($order->user && !$order->user->is_guest) {
                    return redirect()->route('acheteur.orders.show', $order)
                                   ->with('warning', 'Le paiement a été annulé');
                }

                if ($order->guest_token) {
                    return redirect()->route('checkout.guest.confirmation', $order->guest_token)
                                   ->with('warning', 'Le paiement a été annulé');
                }
            }

            return redirect()->route('home')
                           ->with('warning', 'Le paiement a été annulé');

        } catch (\Exception $e) {
            Log::error('Erreur annulation paiement', ['error' => $e->getMessage()]);

            return redirect()->route('home')
                           ->with('error', 'Une erreur est survenue');
        }
    }

    private function canCancel(Request $request, Order $order): bool
    {
        if (!in_array($order->payment_status, ['pending', 'processing'], true)) {
            return false;
        }

        if (auth()->check() && $order->user_id === auth()->id()) {
            return true;
        }

        $token = (string) $request->query('guest_token', '');

        return $order->guest_token !== null && $token !== '' && hash_equals($order->guest_token, $token);
    }

    /**
     * Obtenir les canaux de paiement disponibles
     */
    public function channels()
    {
        try {
            $channels = $this->paiementProService->getAvailableChannels();

            return response()->json([
                'success' => true,
                'channels' => $channels
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération canaux PaiementPro', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des canaux'
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::with('payments')->findOrFail($request->order_id);

            // Vérifier l'autorisation
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $payment = $order->payments()
                           ->where('payment_method', 'paiementpro')
                           ->latest()
                           ->first();

            return response()->json([
                'success' => true,
                'order_status' => $order->payment_status,
                'payment_status' => $payment?->status ?? 'unknown',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_status' => $order->payment_status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut'
            ], 500);
        }
    }

    private function sendConfirmationEmails(Order $order)
    {
        try {
            Log::info("📧 Envoi emails confirmation pour commande {$order->order_number}");

            // ✅ UTILISE VOTRE SERVICE EXISTANT
            $this->emailService->sendAllOrderEmails($order);

            Log::info("✅ Emails envoyés avec succès pour commande {$order->order_number}");

        } catch (\Exception $e) {
            Log::error("❌ Erreur envoi emails pour commande {$order->order_number}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Ne pas faire échouer le paiement à cause d'un problème d'email
        }
    }
}