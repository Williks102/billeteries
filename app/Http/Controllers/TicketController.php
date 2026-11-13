<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Services\TicketPdfService;

class TicketController extends Controller
{
    /**
     * Afficher un billet
     */
    public function show(Ticket $ticket)
    {
        // Vérifier que l'utilisateur peut voir ce billet
        if (auth()->check()) {
            $user = auth()->user();
            
            // L'acheteur peut voir ses propres billets
            if ($user->isAcheteur() && $ticket->order->user_id !== $user->id) {
                abort(403, 'Vous n\'êtes pas autorisé à voir ce billet');
            }
            
            // Le promoteur peut voir les billets de ses événements
            if ($user->isPromoteur() && $ticket->ticketType->event->promoter_id !== $user->id) {
                abort(403, 'Vous n\'êtes pas autorisé à voir ce billet');
            }
        }
        
        $ticket->load(['ticketType.event.category', 'orderItem.order.user']);
        
        return view('tickets.show', compact('ticket'));
    }

    /**
     * Vérifier un billet via QR code (page publique)
     */
    public function verify($ticketCode)
    {
        $ticket = Ticket::where('ticket_code', $ticketCode)
            ->with(['ticketType.event.category', 'orderItem.order.user'])
            ->first();
        
        return view('tickets.verify', compact('ticket'));
    }

    /**
     * Télécharger un billet en PDF
     */
    public function download(Ticket $ticket, TicketPdfService $pdfs)
    {
        // Mêmes règles que Acheteur\OrderController::download : propriétaire de la commande ou admin seulement
        // (les promoteurs et les autres acheteurs sont refusés), jamais de PDF avant paiement confirmé.
        $order = $ticket->linkedOrder();
        $user = auth()->user();

        if ($order === null || ($order->user_id !== $user->id && !$user->isAdmin())) {
            abort(403);
        }

        $order->loadMissing(TicketPdfService::RELATIONS);

        if (!$order->isPaid() || !$ticket->isIssued()) {
            return redirect()->route($user->isAcheteur() ? 'acheteur.tickets.show' : 'tickets.show', $ticket)
                ->with('error', 'Ce billet sera disponible au téléchargement après la confirmation du paiement.');
        }

        return $pdfs->forTicket($order, $ticket)->download($pdfs->ticketFileName($order, $ticket));
    }

    /**
     * Générer les QR codes manquants (commande artisan)
     */
    public function generateMissingQrCodes()
    {
        $tickets = Ticket::whereNull('qr_code')->get();
        $count = 0;
        
        foreach ($tickets as $ticket) {
            if ($ticket->generateQrCode()) {
                $count++;
            }
        }
        
        return response()->json([
            'message' => "$count QR codes générés avec succès",
            'count' => $count
        ]);
    }
}