<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaiementProService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaiementProController extends Controller
{
    private PaiementProService $paiementProService;

    public function __construct(PaiementProService $paiementProService)
    {
        $this->paiementProService = $paiementProService;
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
        Log::info('Réception notification PaiementPro', $request->all());

        // Valider les paramètres requis
        $validator = Validator::make($request->all(), [
            'merchantId' => 'required',
            'referenceNumber' => 'required',
            'amount' => 'required|numeric',
            'responsecode' => 'required'
        ]);

        if ($validator->fails()) {
            Log::warning('Notification PaiementPro invalide', [
                'errors' => $validator->errors(),
                'data' => $request->all()
            ]);
            
            return response('Invalid parameters', 400);
        }

        // Traiter la notification
        $result = $this->paiementProService->handleNotification($request->all());

        if ($result['success']) {
            return response('OK', 200);
        }

        return response('Error: ' . ($result['error'] ?? 'Unknown error'), 400);
    }

    /**
     * Page de retour après paiement
     */
    public function return(Request $request)
    {
        Log::info('Retour utilisateur PaiementPro', $request->all());

        try {
            // Décoder le returnContext s'il existe
            $context = [];
            if ($request->has('returnContext')) {
                parse_str($request->returnContext, $context);
            }

            // Récupérer la commande si possible
            $order = null;
            if (isset($context['order_id'])) {
                $order = Order::with(['user', 'event', 'tickets'])
                             ->find($context['order_id']);
            }

            // Déterminer le statut basé sur les paramètres de retour
            $status = 'pending'; // Par défaut
            
            if ($request->has('responsecode')) {
                $status = $request->responsecode == '0' ? 'success' : 'failed';
            }

            return view('payments.paiementpro.return', [
                'order' => $order,
                'status' => $status,
                'context' => $context,
                'returnData' => $request->all()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur page retour PaiementPro', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return view('payments.paiementpro.return', [
                'order' => null,
                'status' => 'error',
                'context' => [],
                'returnData' => $request->all()
            ]);
        }
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Request $request)
    {
        try {
            $order_id = $request->query('order_id');
            
            if ($order_id) {
                $order = Order::findOrFail($order_id);
                
                // Vérifier l'autorisation
                if ($order->user_id !== auth()->id()) {
                    abort(403);
                }

                return redirect()->route('orders.show', $order)
                               ->with('warning', 'Le paiement a été annulé');
            }

            return redirect()->route('home')
                           ->with('warning', 'Le paiement a été annulé');

        } catch (\Exception $e) {
            Log::error('Erreur annulation paiement', [
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
}