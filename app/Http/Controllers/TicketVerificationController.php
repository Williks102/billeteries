<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour la vérification publique des tickets
 * Utilisé pour les QR codes publics et l'API de vérification
 */
class TicketVerificationController extends Controller
{
    /**
     * Page de vérification publique (via QR code ou URL)
     * Route: GET /verify-ticket/{ticketCode}
     */
    public function verify($ticketCode)
    {
        Log::info("Vérification publique ticket : {$ticketCode}");
        
        try {
            $ticket = Ticket::where('ticket_code', strtoupper(trim($ticketCode)))
                ->with(['ticketType.event.category', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                Log::warning("Ticket non trouvé : {$ticketCode}");
                
                return view('tickets.verify', [
                    'ticket' => null,
                    'error' => 'Billet non trouvé',
                    'ticket_code' => $ticketCode
                ]);
            }

            $verificationInfo = $ticket->getVerificationInfo();
            
            Log::info("Ticket trouvé : {$ticketCode}", [
                'status' => $ticket->status,
                'is_valid' => $verificationInfo['is_valid']
            ]);

            return view('tickets.verify', [
                'ticket' => $ticket,
                'info' => $verificationInfo,
                'error' => null
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur vérification ticket {$ticketCode}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('tickets.verify', [
                'ticket' => null,
                'error' => 'Erreur lors de la vérification',
                'ticket_code' => $ticketCode ?? 'Unknown'
            ]);
        }
    }

    /**
     * API de vérification pour scanner (AJAX/Mobile)
     * Route: GET /api/verify-ticket/{ticketCode}
     */
    public function verifyApi($ticketCode)
    {
        Log::info("API vérification ticket : {$ticketCode}");
        
        try {
            $ticket = Ticket::where('ticket_code', strtoupper(trim($ticketCode)))
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non trouvé',
                    'ticket_code' => $ticketCode
                ], 404);
            }

            $info = $ticket->getVerificationInfo();

            return response()->json([
                'success' => true,
                'ticket' => $info,
                'can_scan' => $info['is_valid'] && $ticket->status !== 'used'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur API vérification ticket {$ticketCode}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur',
                'ticket_code' => $ticketCode
            ], 500);
        }
    }

    /**
     * Scanner un ticket (marquer comme utilisé)
     * Route: POST /api/scan-ticket
     * 
     * ATTENTION: Cette méthode est publique et ne vérifie PAS l'authentification.
     * Elle est destinée aux applications externes ou aux scanners anonymes.
     * Pour un scanner avec authentification promoteur, utiliser PromoteurController::verifyTicket()
     */
    public function scan(Request $request)
    {
        $request->validate([
            'ticket_code' => 'required|string|max:50'
        ]);

        $ticketCode = strtoupper(trim($request->ticket_code));
        
        Log::info("Tentative scan public ticket : {$ticketCode}", [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                Log::warning("Scan échoué - ticket non trouvé : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non trouvé',
                    'ticket_code' => $ticketCode
                ], 404);
            }

            // Vérifier si déjà utilisé
            if ($ticket->status === 'used') {
                Log::warning("Scan échoué - ticket déjà utilisé : {$ticketCode}", [
                    'used_at' => $ticket->used_at
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet déjà utilisé',
                    'ticket' => $ticket->getVerificationInfo(),
                    'used_at' => $ticket->used_at
                ], 400);
            }

            // Valider le ticket
            if (!$ticket->isValidForUse()) {
                Log::warning("Scan échoué - ticket invalide : {$ticketCode}", [
                    'status' => $ticket->status,
                    'validation_info' => $ticket->getVerificationInfo()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet invalide ou expiré',
                    'ticket' => $ticket->getVerificationInfo()
                ], 400);
            }

            // Scanner le ticket
            $scannedBy = auth()->check() ? auth()->user()->name : 'Scanner Public';
            
            if ($ticket->markAsUsed($scannedBy)) {
                Log::info("Scan public réussi : {$ticketCode}", [
                    'scanned_by' => $scannedBy
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Billet scanné avec succès !',
                    'ticket' => [
                        'ticket_code' => $ticket->ticket_code,
                        'event' => [
                            'title' => $ticket->ticketType->event->title,
                            'date' => $ticket->ticketType->event->event_date
                        ],
                        'ticket_type' => $ticket->ticketType->name,
                        'holder' => [
                            'name' => $ticket->orderItem->order->user->name ?? 'N/A'
                        ],
                        'status' => 'used'
                    ],
                    'scanned_at' => now()->toISOString(),
                    'scanned_by' => $scannedBy
                ]);
            } else {
                Log::error("Échec marquage ticket comme utilisé : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors du marquage du ticket'
                ], 500);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Données invalides : ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur scan public ticket', [
                'ticket_code' => $ticketCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur. Veuillez réessayer.'
            ], 500);
        }
    }
}