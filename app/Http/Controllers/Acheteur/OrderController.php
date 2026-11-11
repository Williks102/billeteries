<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

/**
 * Commandes de l'acheteur (ressource : index, show) + actions download / qr-codes.
 */
class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAcheteur() && !Auth::user()->isAdmin()) {
                abort(403, 'Accès non autorisé');
            }
            return $next($request);
        });
    }

    /**
 * Liste des commandes pour l'acheteur - MÉTHODE MANQUANTE
 */
public function index(Request $request)
{
    $user = Auth::user();
    
    try {
        // Construction de la requête
        $query = $user->orders()
            ->with(['event.category', 'orderItems.ticketType', 'tickets.ticketType'])
            ->visibleInBuyerHistory();

        // Filtres
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('event', function($eventQuery) use ($search) {
                      $eventQuery->where('title', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Statistiques
        $stats = [
            'total_orders' => $user->orders()->visibleInBuyerHistory()->count(),
            'paid_orders' => $user->orders()->where('payment_status', 'paid')->count(),
            'total_tickets' => $user->orders()
                ->where('payment_status', 'paid')
                ->with('orderItems')
                ->get()
                ->sum(function($order) {
                    return $order->orderItems->sum('quantity');
                }),
            'total_spent' => $user->orders()
                ->where('payment_status', 'paid')
                ->sum('total_amount')
        ];

        return view('acheteur.orders', compact('orders', 'stats'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur acheteur orders: ' . $e->getMessage());
        
        return view('acheteur.orders', [
            'orders' => collect()->paginate(10),
            'stats' => [
                'total_orders' => 0,
                'paid_orders' => 0, 
                'total_tickets' => 0,
                'total_spent' => 0
            ]
        ])->with('error', 'Erreur lors du chargement des commandes.');
    }
}

    /**
     * Détail d'une commande
     */
    public function show(Order $order)
    {
        // Vérifier que l'utilisateur peut voir cette commande
        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $order->load([
            'event.category', 
            'orderItems.ticketType', 
            'tickets.ticketType',
            'commission'
        ]);

        return view('acheteur.order-detail', [
            'order' => $order,
            'awaitingPayment' => $order->isAwaitingPayment(),
            'paymentInfo' => \App\Services\CheckoutService::manualPaymentInfo($order->payment_method),
            'paymentDeadline' => $order->paymentDeadline(),
            'demoSimulatedPayment' => $order->isPaid() && $order->payment_reference === \App\Services\CheckoutService::DEMO_REFERENCE,
        ]);
    }

    public function download(Order $order)
{
    // Autorisation : le PDF contient les billets et leurs QR codes. Réservé au propriétaire de la commande
    // (ou à un admin) ; jamais de PDF de billets avant la confirmation du paiement.
    if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
        abort(403);
    }

    if (!$order->isPaid()) {
        return redirect()->route('acheteur.orders.show', $order)
            ->with('error', 'Vos billets seront disponibles au téléchargement après la confirmation du paiement.');
    }

    // Charger les relations nécessaires
    $order->load(['event.category', 'tickets.ticketType', 'orderItems.ticketType', 'user']);
    
    // Générer le PDF avec DomPDF
    $pdf = \PDF::loadView('acheteur.tickets-pdf', compact('order'));
    
    // Configuration du PDF
    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);
    
    // Nom du fichier
    $fileName = 'billets-' . $order->order_number . '-' . $order->event->title . '.pdf';
    $fileName = \Str::slug($fileName) . '.pdf';
    
    // Télécharger le PDF
    return $pdf->download($fileName);
}

/**
 * Récupérer les QR codes d'une commande
 */
public function qrCodes(Order $order)
{
    try {
        // Vérifier l'autorisation
        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }
        
        $order->load(['tickets.ticketType', 'event']);
        
        $tickets = [];
        
        foreach ($order->tickets as $ticket) {
            $qrBase64 = null;
            
            try {
                $qrService = app(\App\Services\QRCodeService::class);
                $qrBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                
                \Log::info("QR généré pour le billet #{$ticket->id}: " . 
                    ($qrBase64 ? 'SUCCESS' : 'FAILED'));
                    
            } catch (\Exception $e) {
                \Log::error("Erreur génération QR pour le billet #{$ticket->id} (" . get_class($e) . ")");
            }
            
            $tickets[] = [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'qr_code' => $qrBase64,
                'event_title' => $ticket->ticketType->event->title ?? 'N/A',
                'ticket_type' => $ticket->ticketType->name ?? 'N/A',
                'status' => $ticket->status,
                'seat_number' => $ticket->seat_number
            ];
        }
        
        return response()->json([
            'success' => true,
            'tickets' => $tickets,
            'order_number' => $order->order_number
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Erreur getOrderQRCodes: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération des QR codes',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
}
