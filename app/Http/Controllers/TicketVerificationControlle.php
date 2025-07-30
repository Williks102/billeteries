// app/Http/Controllers/TicketVerificationController.php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketVerificationController extends Controller
{
    /**
     * Page de vérification publique (via QR code ou URL)
     */
    public function verify($ticketCode)
    {
        Log::info("Tentative vérification ticket : {$ticketCode}");
        
        try {
            $ticket = Ticket::where('ticket_code', $ticketCode)
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
                'error' => $e->getMessage()
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
     */
    public function verifyApi($ticketCode)
    {
        Log::info("API vérification ticket : {$ticketCode}");
        
        try {
            $ticket = Ticket::where('ticket_code', $ticketCode)
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
                'error' => $e->getMessage()
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
     */
    public function scan(Request $request)
    {
        $request->validate([
            'ticket_code' => 'required|string'
        ]);

        $ticketCode = strtoupper(trim($request->ticket_code));
        
        Log::info("Tentative scan ticket : {$ticketCode}", [
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
            $scannedBy = auth()->user() ? auth()->user()->name : 'Système';
            
            if ($ticket->markAsUsed($scannedBy)) {
                Log::info("Scan réussi : {$ticketCode}", [
                    'scanned_by' => $scannedBy
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Billet scanné avec succès !',
                    'ticket' => $ticket->getVerificationInfo(),
                    'scanned_at' => $ticket->used_at
                ]);
            } else {
                Log::error("Scan échoué - erreur markAsUsed : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors du scan',
                    'ticket_code' => $ticketCode
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur scan ticket {$ticketCode}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur lors du scan',
                'ticket_code' => $ticketCode
            ], 500);
        }
    }
}
