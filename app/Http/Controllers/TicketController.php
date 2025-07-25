<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
            if ($user->isPromoteur() && $ticket->ticketType->event->promoteur_id !== $user->id) {
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
    public function download(Ticket $ticket)
    {
        // Vérifier les permissions
        $user = auth()->user();
        
        if ($user->isAcheteur() && $ticket->order->user_id !== $user->id) {
            abort(403);
        }
        
        if ($user->isPromoteur() && $ticket->ticketType->event->promoteur_id !== $user->id) {
            abort(403);
        }
        
        $ticket->load(['ticketType.event.category', 'orderItem.order.user']);
        
        $pdf = Pdf::loadView('tickets.pdf', compact('ticket'))
            ->setPaper('a4', 'portrait');
        
        $filename = 'billet_' . $ticket->ticket_code . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Générer les QR codes manquants (commande artisan)
     */
    public function generateMissingQrCodes()
    {
        $tickets = Ticket::whereNull('qr_code_path')->get();
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