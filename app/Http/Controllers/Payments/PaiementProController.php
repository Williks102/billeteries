<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaiementProService;
use App\Jobs\SendOrderEmailsJob;
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
            'channel' => 'nullable|in:CARD,MOMO,OMCIV2,FLOOZ,PAYPAL'
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
                'request' => $request->all()
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
        Log::info('🔔 Réception notification PaiementPro', $request->all());

        // Valider les paramètres selon la documentation PaiementPro
        $validator = Validator::make($request->all(), [
            'merchantId' => 'required',
            'referenceNumber' => 'required',
            'amount' => 'required|numeric',
            'responsecode' => 'required',
            'transactiondt' => 'required',
            
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Notification PaiementPro invalide', [
                'errors' => $validator->errors(),
                'data' => $request->all()
            ]);
            
            return response('Invalid parameters', 400);
        }

        try {
            // Traiter la notification selon la documentation
            $result = $this->paiementProService->handleNotification($request->all());

            if ($result['success']) {
                Log::info('✅ Notification PaiementPro traitée avec succès');
                
                // ENVOI DES EMAILS après paiement réussi
                if (isset($result['order']) && $result['payment_successful']) {
                    $this->sendConfirmationEmails($result['order']);
                }
                
                return response('OK', 200);
            }

            Log::error('❌ Échec traitement notification', $result);
            return response('Error: ' . ($result['error'] ?? 'Unknown error'), 400);

        } catch (\Exception $e) {
            Log::error('💥 Exception notification PaiementPro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response('Internal error', 500);
        }
    }

    /**
     * Page de retour après paiement
     */
     public function return(Request $request)
    {
        Log::info('🔙 Retour utilisateur PaiementPro', $request->all());

        try {
            // Décoder le returnContext selon la documentation
            $context = [];
            if ($request->has('returnContext')) {
                parse_str($request->returnContext, $context);
            }

            // Récupérer la commande
            $order = null;
            if (isset($context['order_id'])) {
                $order = Order::with(['user', 'event', 'tickets', 'orderItems.ticketType'])
                             ->find($context['order_id']);
            } elseif ($request->has('referenceNumber')) {
                $order = Order::with(['user', 'event', 'tickets', 'orderItems.ticketType'])
                             ->where('order_number', $request->referenceNumber)
                             ->first();
            }

            // Déterminer le statut basé sur responsecode (selon doc PaiementPro)
            $paymentStatus = 'pending';
            $statusMessage = 'Paiement en cours de traitement';
            
            if ($request->has('responsecode')) {
                switch ($request->responsecode) {
                    case '0':
                        $paymentStatus = 'success';
                        $statusMessage = 'Paiement réussi !';
                        break;
                    case '-1':
                        $paymentStatus = 'failed';
                        $statusMessage = 'Paiement échoué';
                        break;
                    default:
                        $paymentStatus = 'pending';
                        $statusMessage = 'Statut de paiement en attente';
                }
            }

            // Redirection intelligente selon le type d'utilisateur
            if ($order) {
                if ($order->user && !$order->user->is_guest) {
                    // Utilisateur connecté -> dashboard
                    if ($paymentStatus === 'success') {
                        return redirect()->route('acheteur.orders.show', $order)
                                       ->with('success', $statusMessage);
                    } else {
                        return redirect()->route('acheteur.orders.show', $order)
                                       ->with('error', $statusMessage);
                    }
                } else {
                    // Invité -> page de confirmation publique
                    $token = $order->guest_token;
                    if ($token) {
                        if ($paymentStatus === 'success') {
                            return redirect()->route('checkout.guest.confirmation', $token)
                                           ->with('success', $statusMessage);
                        } else {
                            return redirect()->route('checkout.guest.confirmation', $token)
                                           ->with('error', $statusMessage);
                        }
                    }
                }
            }

            // Fallback vers la page d'accueil
            return redirect()->route('home')
                           ->with($paymentStatus === 'success' ? 'success' : 'error', $statusMessage);

        } catch (\Exception $e) {
            Log::error('💥 Erreur page retour PaiementPro', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return redirect()->route('home')
                           ->with('error', 'Une erreur est survenue lors du retour de paiement');
        }
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Request $request)
    {
        Log::info('❌ Annulation paiement PaiementPro', $request->all());

        try {
            $order_id = $request->query('order_id');
            
            if ($order_id) {
                $order = Order::find($order_id);
                
                if ($order) {
                    // Marquer comme annulé
                    $order->update(['payment_status' => 'cancelled']);
                    
                    // Redirection selon le type d'utilisateur
                    if ($order->user && !$order->user->is_guest) {
                        return redirect()->route('acheteur.orders.show', $order)
                                       ->with('warning', 'Le paiement a été annulé');
                    } else {
                        // Invité
                        if ($order->guest_token) {
                            return redirect()->route('checkout.guest.confirmation', $order->guest_token)
                                           ->with('warning', 'Le paiement a été annulé');
                        }
                    }
                }
            }

            return redirect()->route('home')
                           ->with('warning', 'Le paiement a été annulé');

        } catch (\Exception $e) {
            Log::error('💥 Erreur annulation paiement', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return redirect()->route('home')
                           ->with('error', 'Une erreur est survenue');
        }
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
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut'
            ], 500);
        }
    }

    public function testNotification(Request $request)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $testData = [
            'merchantId' => config('services.paiementpro.merchant_id'),
            'referenceNumber' => $request->reference ?? 'TEST-ORDER-123',
            'amount' => $request->amount ?? 1000,
            'responsecode' => $request->status ?? '0', // 0 = succès, -1 = échec
            'transactiondt' => now()->format('Y-m-d H:i:s'),
            'customerId' => '1',
            'returnContext' => 'order_id=1&test=true',
            'hashcode' => 'test_hash'
        ];

        return $this->notification(new Request($testData));
    }
    private function sendConfirmationEmails(Order $order)
    {
        try {
            Log::info("📧 Envoi emails confirmation pour commande {$order->order_number}");

            // Mise en file d'attente pour ne pas bloquer la réponse webhook
            SendOrderEmailsJob::dispatch($order->id);

            Log::info("✅ Emails mis en file d'attente pour commande {$order->order_number}");

        } catch (\Exception $e) {
            Log::error("❌ Erreur envoi emails pour commande {$order->order_number}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Ne pas faire échouer le paiement à cause d'un problème d'email
        }
    }
}