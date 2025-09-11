<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur pour la vérification publique des tickets - VERSION COMPLÈTE CORRIGÉE
 * Corrige l'erreur "Undefined array key 'time'" et l'erreur SQL used_at
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

            $verificationInfo = $this->getTicketVerificationInfo($ticket);
            
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
                'ticket_code' => $ticketCode ?? 'N/A'
            ]);
        }
    }

    /**
     * API de vérification (JSON) 
     * Route: GET /api/verify-ticket/{ticketCode}
     */
    public function verifyApi($ticketCode)
    {
        Log::info("Vérification API ticket : {$ticketCode}");
        
        try {
            $ticket = Ticket::where('ticket_code', strtoupper(trim($ticketCode)))
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non trouvé'
                ], 404);
            }

            $verificationInfo = $this->getTicketVerificationInfo($ticket);
            
            return response()->json([
                'success' => true,
                'ticket' => [
                    'code' => $ticket->ticket_code,
                    'status' => $ticket->status,
                    'info' => $verificationInfo
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur vérification API ticket {$ticketCode}", [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification'
            ], 500);
        }
    }

    /**
     * Vue publique - LECTURE SEULE
     * Affiche uniquement la validité sans informations sensibles
     * Route: GET /verify-ticket/{ticketCode}
     */
    public function publicVerify($ticketCode)
    {
        Log::info("Vérification publique ticket : {$ticketCode}", [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        try {
            $ticket = Ticket::where('ticket_code', strtoupper(trim($ticketCode)))
                ->with(['ticketType.event'])
                ->first();

            if (!$ticket) {
                return view('tickets.public-verify', [
                    'ticket' => null,
                    'error' => 'Billet non trouvé',
                    'ticket_code' => $ticketCode
                ]);
            }

            // Informations publiques limitées (sans données personnelles)
            $publicInfo = [
                'is_valid' => $ticket->status === 'sold' || $ticket->status === 'used',
                'status' => $ticket->status,
                'status_message' => $this->getPublicStatusMessage($ticket),
                'event' => [
                    'title' => $ticket->ticketType->event->title,
                    'date' => $ticket->ticketType->event->event_date->format('d/m/Y'),
                    'time' => $ticket->ticketType->event->event_time->format('H:i'),
                    'venue' => $ticket->ticketType->event->venue ?? 'Lieu à définir',
                    'address' => $ticket->ticketType->event->address ?? null,
                    'location' => $ticket->ticketType->event->venue ?? 'Lieu à définir',
                    'category' => $ticket->ticketType->event->category->name ?? null
                ],
                'ticket_type' => $ticket->ticketType->name,
                // PAS d'informations personnelles ici
            ];

            return view('tickets.public-verify', [
                'ticket' => $ticket,
                'info' => $publicInfo,
                'error' => null
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur vérification publique : {$ticketCode}", [
                'error' => $e->getMessage()
            ]);
            
            return view('tickets.public-verify', [
                'ticket' => null,
                'error' => 'Erreur lors de la vérification',
                'ticket_code' => $ticketCode
            ]);
        }
    }

    /**
     * Vue authentifiée - CONTRÔLE COMPLET
     * Pour les promoteurs/admins avec actions de scan
     * Route: GET /scanner/verify/{ticketCode}
     * Middleware: auth + role check
     */
    public function authenticatedVerify($ticketCode)
    {
        $user = auth()->user();
        
        Log::info("Vérification authentifiée ticket : {$ticketCode}", [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'ip' => request()->ip()
        ]);
        
        try {
            $ticket = Ticket::where('ticket_code', strtoupper(trim($ticketCode)))
                ->with(['ticketType.event.category', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                return view('tickets.authenticated-verify', [
                    'ticket' => null,
                    'error' => 'Billet non trouvé',
                    'ticket_code' => $ticketCode
                ]);
            }

            // Vérifier les permissions (promoteur du bon événement ou admin)
            if ($user->role === 'promoteur' && 
                $ticket->ticketType->event->promoter_id !== $user->id) {
                
                return view('tickets.authenticated-verify', [
                    'ticket' => null,
                    'error' => 'Vous n\'êtes pas autorisé à gérer ce billet',
                    'ticket_code' => $ticketCode
                ]);
            }

            // Informations complètes pour utilisateurs authentifiés
            $fullInfo = [
                'is_valid' => $ticket->status === 'sold' || $ticket->status === 'used',
                'can_be_used' => $ticket->status === 'sold',
                'status' => $ticket->status,
                'status_message' => $this->getFullStatusMessage($ticket),
                'ticket_type' => $ticket->ticketType->name,
                'event' => [
                    'title' => $ticket->ticketType->event->title,
                    'date' => $ticket->ticketType->event->event_date->format('d/m/Y'),
                    'time' => $ticket->ticketType->event->event_time->format('H:i'),
                    'venue' => $ticket->ticketType->event->venue ?? 'Lieu à définir',
                    'address' => $ticket->ticketType->event->address ?? null,
                    'location' => $ticket->ticketType->event->venue ?? 'Lieu à définir',
                    'category' => $ticket->ticketType->event->category->name ?? null,
                    'status' => $ticket->ticketType->event->status
                ],
                'holder' => [
                    'name' => $ticket->orderItem->order->user->name ?? 'N/A',
                    'email' => $ticket->orderItem->order->user->email ?? 'N/A'
                ],
                'order' => $ticket->orderItem->order ? [
                    'number' => $ticket->orderItem->order->order_number,
                    'date' => $ticket->orderItem->order->created_at->format('d/m/Y H:i')
                ] : null,
                'used_at' => $ticket->used_at ? $ticket->used_at->format('d/m/Y à H:i') : null
            ];

            return view('tickets.authenticated-verify', [
                'ticket' => $ticket,
                'info' => $fullInfo,
                'error' => null,
                'can_scan' => $user->role === 'admin' || $user->role === 'promoteur'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur vérification authentifiée : {$ticketCode}", [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return view('tickets.authenticated-verify', [
                'ticket' => null,
                'error' => 'Erreur lors de la vérification',
                'ticket_code' => $ticketCode
            ]);
        }
    }

    /**
     * Scanner un ticket - AUTHENTIFIÉ UNIQUEMENT
     * Route: POST /api/authenticated-scan
     * Middleware: auth + role check
     */
    public function authenticatedScan(Request $request)
    {
        $user = auth()->user();
        
        // Vérifier les rôles autorisés
        if (!in_array($user->role, ['admin', 'promoteur'])) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
                'message' => 'Seuls les administrateurs et promoteurs peuvent scanner des billets'
            ], 403);
        }

        Log::info('Scan authentifié', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'ip' => $request->ip()
        ]);

        try {
            $validated = $request->validate([
                'ticket_code' => 'required|string|max:20'
            ]);

            $ticketCode = strtoupper(trim($validated['ticket_code']));
            
            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non trouvé',
                    'message' => 'Le code de billet fourni n\'existe pas'
                ], 404);
            }

            // Vérifier les permissions promoteur
            if ($user->role === 'promoteur' && 
                $ticket->ticketType->event->promoter_id !== $user->id) {
                
                return response()->json([
                    'success' => false,
                    'error' => 'Non autorisé',
                    'message' => 'Vous ne pouvez scanner que les billets de vos événements'
                ], 403);
            }

            // Vérifications de statut
            if ($ticket->status === 'used') {
                return response()->json([
                    'success' => false,
                    'error' => 'Billet déjà utilisé',
                    'message' => 'Ce billet a déjà été utilisé le ' . $ticket->updated_at->format('d/m/Y à H:i'),
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'status' => 'used',
                        'used_at' => $ticket->updated_at->format('d/m/Y H:i')
                    ]
                ], 400);
            }

            if ($ticket->status !== 'sold') {
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non valide',
                    'message' => 'Ce billet ne peut pas être utilisé (statut: ' . $ticket->status . ')',
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'status' => $ticket->status
                    ]
                ], 400);
            }

            $event = $ticket->ticketType->event;
            if ($event->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'error' => 'Événement non disponible',
                    'message' => 'L\'événement associé à ce billet n\'est plus disponible'
                ], 400);
            }

            // Marquer comme utilisé (CORRECTIF : utilise updated_at)
            try {
                \DB::beginTransaction();
                
                $ticket->status = 'used';
                $ticket->save(); // updated_at sera automatiquement mis à jour
                
                \DB::commit();

                Log::info("Ticket scanné avec succès : {$ticketCode}", [
                    'scanned_by_user_id' => $user->id,
                    'ticket_id' => $ticket->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Billet scanné avec succès',
                    'data' => [
                        'ticket_code' => $ticket->ticket_code,
                        'event_title' => $event->title,
                        //'holder_name' => $ticket->orderItem->order->user->name,
                        'ticket_info' => [
                            'code' => $ticket->ticket_code,
                            'event' => $ticket->ticketType->event->title,
                            'type' => $ticket->ticketType->name,
                            'status' => 'used'
                        ],
                        'scanned_by' => $user->name,
                        'scanned_at' => now()->toISOString()
                    ]
                ]);

            } catch (\Exception $dbError) {
                \DB::rollback();
                
                Log::error("Échec scan authentifié : {$ticketCode}", [
                    'user_id' => $user->id,
                    'error' => $dbError->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur de scan',
                    'message' => 'Impossible de marquer le billet comme utilisé'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur scan authentifié', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors du scan'
            ], 500);
        }
    }

    /**
     * Scanner un ticket (marquer comme utilisé) - VERSION PUBLIQUE
     * Route: POST /api/scan-ticket
     * 
     * CORRIGE L'ERREUR SQL en n'utilisant plus markAsUsed() ni used_at
     */
    public function scan(Request $request)
    {
        Log::info('Tentative scan public ticket', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $validated = $request->validate([
                'ticket_code' => 'required|string|max:20'
            ]);

            $ticketCode = strtoupper(trim($validated['ticket_code']));
            
            Log::info("Tentative scan public : {$ticketCode}");

            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                Log::warning("Ticket non trouvé pour scan : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non trouvé',
                    'message' => 'Le code de billet fourni n\'existe pas dans notre système'
                ], 404);
            }

            if ($ticket->status === 'used') {
                Log::info("Tentative scan ticket déjà utilisé : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet déjà utilisé',
                    'message' => 'Ce billet a déjà été utilisé le ' . $ticket->updated_at->format('d/m/Y à H:i'),
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'status' => 'used',
                        'used_at' => $ticket->updated_at->format('d/m/Y H:i')
                    ]
                ], 400);
            }

            if ($ticket->status !== 'sold') {
                Log::warning("Tentative scan ticket non valide : {$ticketCode}, statut: {$ticket->status}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Billet non valide',
                    'message' => 'Ce billet ne peut pas être utilisé (statut: ' . $ticket->status . ')',
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'status' => $ticket->status
                    ]
                ], 400);
            }

            $event = $ticket->ticketType->event;
            if ($event->status !== 'published') {
                Log::warning("Tentative scan pour événement non publié : {$ticketCode}");
                
                return response()->json([
                    'success' => false,
                    'error' => 'Événement non disponible',
                    'message' => 'L\'événement associé à ce billet n\'est plus disponible'
                ], 400);
            }

            $scannedBy = auth()->check() ? auth()->user()->name : 'Unknown';

            // CORRECTIF : Marquer comme utilisé en utilisant updated_at
            try {
                \DB::beginTransaction();
                
                $ticket->status = 'used';
                $ticket->save(); // updated_at sera automatiquement mis à jour par Eloquent
                
                \DB::commit();

                Log::info("Scan public réussi : {$ticketCode}", [
                    'ticket_id' => $ticket->id,
                    'scanned_by' => $scannedBy
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Billet scanné avec succès',
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'event' => $event->title,
                        'holder' => $ticket->orderItem->order->user->name ?? 'Unknown',
                        'status' => 'used',
                        'scanned_at' => now()->toISOString(),
                        'scanned_by' => $scannedBy
                    ]
                ]);

            } catch (\Exception $saveError) {
                \DB::rollback();
                
                Log::error("Erreur sauvegarde scan : {$ticketCode}", [
                    'error' => $saveError->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur de scan',
                    'message' => 'Impossible de marquer le billet comme utilisé'
                ], 500);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Données invalides',
                'message' => 'Le code de billet fourni n\'est pas valide'
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur scan public', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors du scan'
            ], 500);
        }
    }

    /**
     * Test de l'API (endpoint de diagnostic)
     * Route: GET /api/test-scan
     */
    public function testScan()
    {
        $columns = Schema::getColumnListing('tickets');
        
        return response()->json([
            'success' => true,
            'message' => 'API de scan opérationnelle',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.1-fixed',
            'database_info' => [
                'tickets_columns' => $columns,
                'has_used_at' => in_array('used_at', $columns),
                'has_scanned_by' => in_array('scanned_by', $columns),
                'solution' => 'Utilise updated_at comme substitute pour used_at'
            ]
        ]);
    }

    /**
     * Obtenir les informations de vérification d'un ticket
     * Version CORRIGÉE avec toutes les données requises par la vue
     */
    private function getTicketVerificationInfo($ticket)
    {
        $event = $ticket->ticketType->event;
        $order = $ticket->orderItem->order ?? null;
        $user = $order->user ?? null;

        $usedAt = null;
        if ($ticket->status === 'used') {
            if ($ticket->used_at) {
                $usedAt = $ticket->used_at->format('d/m/Y à H:i');
            } else {
                // Fallback sur updated_at si used_at n'existe pas
                $usedAt = $ticket->updated_at->format('d/m/Y à H:i');
            }
        }

        // CORRECTION : Fournir TOUTES les données utilisées dans la vue
        $eventDate = $event->event_date;
        
        return [
            'is_valid' => $ticket->status === 'sold' || $ticket->status === 'used',
            'can_be_used' => $ticket->status === 'sold',
            'status' => $ticket->status,
            'status_message' => $this->getStatusMessage($ticket, $usedAt),
            'ticket_type' => $ticket->ticketType->name,
            'event' => [
                'title' => $event->title,
                'date' => $eventDate->format('d/m/Y'),      // Date seule
                'time' => $eventDate->format('H:i'),        // Heure seule
                'venue' => $event->venue ?? 'Lieu à définir',        // AJOUT : venue
                'address' => $event->address ?? null,       // AJOUT : address
                'location' => $event->location ?? $event->venue ?? 'Lieu à définir', // Alternative/fallback
                'category' => $event->category->name ?? null, // AJOUT : category
                'status' => $event->status
            ],
            'holder' => [
                'name' => $user->name ?? 'N/A',
                'email' => $user->email ?? 'N/A'
            ],
            'order' => $order ? [
                'number' => $order->order_number,
                'date' => $order->created_at->format('d/m/Y H:i')
            ] : null,
            'used_at' => $usedAt
        ];
    }

    /**
     * Message de statut human-readable
     */
    private function getStatusMessage($ticket, $usedAt = null)
    {
        return match($ticket->status) {
            'sold' => 'Billet valide et non utilisé',
            'used' => 'Billet déjà utilisé' . ($usedAt ? " le {$usedAt}" : ''),
            'cancelled' => 'Billet annulé',
            'available' => 'Billet disponible (non vendu)',
            default => 'Statut inconnu: ' . $ticket->status
        };
    }

    /**
     * Message de statut pour vue publique (limité)
     */
    private function getPublicStatusMessage($ticket)
    {
        return match($ticket->status) {
            'sold' => 'Billet valide',
            'used' => 'Billet utilisé',
            'cancelled' => 'Billet annulé',
            'available' => 'Billet non vendu',
            default => 'Statut: ' . $ticket->status
        };
    }

    /**
     * Message de statut complet pour vue authentifiée
     */
    private function getFullStatusMessage($ticket)
    {
        $usedAt = null;
        if ($ticket->status === 'used') {
            if ($ticket->used_at) {
                $usedAt = $ticket->used_at->format('d/m/Y à H:i');
            } else {
                $usedAt = $ticket->updated_at->format('d/m/Y à H:i');
            }
        }

        return match($ticket->status) {
            'sold' => 'Billet valide et non utilisé - Prêt pour le scan',
            'used' => 'Billet déjà utilisé' . ($usedAt ? " le {$usedAt}" : ''),
            'cancelled' => 'Billet annulé - Ne peut pas être utilisé',
            'available' => 'Billet disponible (non vendu)',
            default => 'Statut inconnu: ' . $ticket->status
        };
    }
}