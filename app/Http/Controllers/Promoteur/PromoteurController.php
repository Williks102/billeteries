<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\{Event, Order, Commission, Ticket};
use Carbon\Carbon;

class PromoteurController extends Controller
{
    /**
     * Dashboard principal du promoteur - CORRIGÉ
     */
   public function dashboard()
{
    try {
        $promoteurId = Auth::id();
        
        // Statistiques principales - CORRIGÉES
        $stats = [
            // Événements
            'total_events' => Event::where('promoter_id', $promoteurId)->count(),
            'published_events' => Event::where('promoter_id', $promoteurId)
                ->where('status', 'published')->count(),
            'events_this_month' => Event::where('promoter_id', $promoteurId)
                ->whereMonth('created_at', now()->month)->count(),
            
            // Billets vendus
            'total_tickets_sold' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })->where('status', '!=', 'available')->count(),
            
            'tickets_this_week' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })->where('status', '!=', 'available')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count(),
            
            // Revenus NETS (ce que garde le promoteur après commission plateforme)
            'total_revenue' => Commission::where('promoter_id', $promoteurId)
                ->where('status', 'paid')
                ->sum('net_amount'), // Le montant NET que garde le promoteur
            
            'monthly_revenue' => Commission::where('promoter_id', $promoteurId)
                ->where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('net_amount'),
            
            // Calcul croissance
            'revenue_growth' => $this->calculateRevenueGrowth($promoteurId),
        ];
        
        // Événements récents
        $recentEvents = Event::where('promoter_id', $promoteurId)
            ->with(['ticketTypes', 'category'])
            ->latest()
            ->take(5)
            ->get();
        
        // Commandes récentes sur mes événements
        $recentOrders = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->with(['user', 'event'])
            ->where('payment_status', 'paid')
            ->latest()
            ->take(5)
            ->get();
        
        // Événements à venir
        $upcomingEvents = Event::where('promoter_id', $promoteurId)
            ->where('event_date', '>', now())
            ->orderBy('event_date')
            ->take(3)
            ->get();
        
        return view('promoteur.dashboard', compact(
            'stats', 
            'recentEvents', 
            'recentOrders', 
            'upcomingEvents'
        ));
        
    } catch (\Exception $e) {
        \Log::error('Erreur dashboard promoteur: ' . $e->getMessage());
        
        // Données par défaut en cas d'erreur
        $stats = [
            'total_events' => 0,
            'published_events' => 0,
            'events_this_month' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'total_tickets_sold' => 0,
            'tickets_this_week' => 0,
            'revenue_growth' => 0,
        ];
        
        return view('promoteur.dashboard', [
            'stats' => $stats,
            'recentEvents' => collect(),
            'recentOrders' => collect(),
            'upcomingEvents' => collect(),
        ])->with('error', 'Erreur lors du chargement du dashboard');
    }
}


    /**
     * Profil du promoteur
     */
    public function profile()
{
    $user = Auth::user(); // ✅ AJOUTER cette ligne
    $promoteur = $user; // Alias pour compatibilité
    
    try {
        $promoteurId = $user->id;
        
        // Statistiques du promoteur
        $stats = [
            'total_events' => Event::where('promoter_id', $promoteurId)->count(),
            'published_events' => Event::where('promoter_id', $promoteurId)->where('status', 'published')->count(),
            'total_revenue' => Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'total_commissions' => Commission::where('promoter_id', $promoteurId)->sum('net_amount'),
            'paid_commissions' => Commission::where('promoter_id', $promoteurId)
                ->where('status', 'paid')
                ->sum('commission_amount'),
            'pending_commissions' => Commission::where('promoter_id', $promoteurId)
                ->where('status', 'pending')
                ->sum('commission_amount'),
            'total_tickets_sold' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })->where('status', '!=', 'available')->count(),
            'join_date' => $user->created_at,
            'last_event' => Event::where('promoter_id', $promoteurId)->latest()->first()
        ];
        
        // ✅ PASSER LES DEUX VARIABLES
        return view('promoteur.profile', compact('user', 'promoteur', 'stats'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur lors du chargement du profil promoteur: ' . $e->getMessage());
        
        return view('promoteur.profile', [
            'user' => $user, // ✅ AJOUTER
            'promoteur' => $user, // ✅ AJOUTER
            'stats' => [
                'total_events' => 0,
                'published_events' => 0,
                'total_revenue' => 0,
                'total_commissions' => 0,
                'paid_commissions' => 0,
                'pending_commissions' => 0,
                'total_tickets_sold' => 0,
                'join_date' => $user->created_at,
                'last_event' => null
            ]
        ])->with('error', 'Erreur lors du chargement des statistiques');
    }
}

    /**
     * Mettre à jour le profil du promoteur
     */
    public function updateProfile(Request $request)
    {
        $promoteur = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $promoteur->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Vérifier le mot de passe actuel si un nouveau est fourni
        if ($request->filled('password')) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Le mot de passe actuel est requis']);
            }
            
            if (!Hash::check($request->current_password, $promoteur->password)) {
                return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect']);
            }
        }

        $updateData = $request->only([
            'name', 'email', 'phone', 'bio', 'website', 
            'facebook', 'instagram', 'twitter'
        ]);

        // Mettre à jour le mot de passe si fourni
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $promoteur->update($updateData);

        return back()->with('success', 'Profil mis à jour avec succès');
    }

    /**
     * Mettre à jour l'avatar du promoteur
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048|mimes:jpeg,png,jpg'
        ]);

        $promoteur = Auth::user();

        // Supprimer l'ancien avatar
        if ($promoteur->avatar) {
            Storage::disk('public')->delete($promoteur->avatar);
        }

        // Sauvegarder le nouveau
        $avatarPath = $request->file('avatar')->store('avatars', 'public');
        $promoteur->update(['avatar' => $avatarPath]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar mis à jour avec succès',
            'avatar_url' => Storage::url($avatarPath)
        ]);
    }

    /**
     * API pour obtenir le nombre de notifications - CORRIGÉ
     */
    public function getNotificationsCount()
    {
        $promoteurId = Auth::id();
        
        try {
            // Billets en attente de scan pour les événements du jour
            $pendingScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'sold') // Vendus mais pas utilisés
                ->whereHas('ticketType.event', function($query) {
                    $query->whereDate('event_date', today());
                })
                ->count();

            // Commandes en attente de validation
            $pendingOrders = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'pending')
                ->count();

            // Événements nécessitant une attention
            $eventsNeedingAttention = Event::where('promoter_id', $promoteurId)
                ->where(function($query) {
                    $query->where('status', 'pending')
                        ->orWhere(function($q) {
                            $q->where('status', 'published')
                              ->where('event_date', '<=', now()->addDays(7))
                              ->whereDoesntHave('ticketTypes', function($tt) {
                                  $tt->where('is_active', true);
                              });
                        });
                })
                ->count();

            return response()->json([
                'pending_scans' => $pendingScans,
                'pending_orders' => $pendingOrders,
                'events_attention' => $eventsNeedingAttention,
                'total_notifications' => $pendingScans + $pendingOrders + $eventsNeedingAttention
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des notifications promoteur: ' . $e->getMessage());
            
            return response()->json([
                'pending_scans' => 0,
                'pending_orders' => 0,
                'events_attention' => 0,
                'total_notifications' => 0
            ]);
        }
    }

    /**
     * Statistiques rapides pour le dashboard (API) - CORRIGÉ
     */
    public function getDashboardStats()
    {
        $promoteurId = Auth::id();
        
        return response()->json([
            'today_revenue' => Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('payment_status', 'paid')
                ->whereDate('created_at', today())
                ->sum('total_amount'),
                
            'today_tickets' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->whereDate('created_at', today())
                ->count(),
                
            'today_scans' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'used')
                ->whereDate('updated_at', today())
                ->count()
        ]);
    }
    /**
 * Calculer la croissance des revenus par rapport au mois précédent
 */
private function calculateRevenueGrowth($promoteurId)
{
    $thisMonth = Commission::where('promoter_id', $promoteurId)
        ->where('status', 'paid')
        ->whereMonth('created_at', now()->month)
        ->sum('net_amount');
    
    $lastMonth = Commission::where('promoter_id', $promoteurId)
        ->where('status', 'paid')
        ->whereMonth('created_at', now()->subMonth()->month)
        ->sum('net_amount');
    
    if ($lastMonth > 0) {
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }
    
    return $thisMonth > 0 ? 100 : 0;
}
}