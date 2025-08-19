<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des utilisateurs (MIGRÉ depuis AdminController::users)
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Filtres existants de votre code
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        // Ajout du filtre par statut de vérification
        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }
        
        $users = $query->latest()
                      ->paginate(20)
                      ->appends($request->query());

        // Ajout de statistiques pour améliorer la vue
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'promoteurs' => User::where('role', 'promoteur')->count(),
            'acheteurs' => User::where('role', 'acheteur')->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Formulaire de création (MIGRÉ depuis AdminController::createUser)
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Sauvegarde nouvel utilisateur (MIGRÉ depuis AdminController::storeUser)
     */
    public function store(Request $request)
    {
        // Validation exacte de votre code existant
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,promoteur,acheteur',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'email_verified_at' => now(), // Auto-vérifier les comptes créés par admin
            ]);

            // Log de l'action pour traçabilité
            \Log::info('Utilisateur créé par admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'new_user_id' => $user->id,
                'new_user_email' => $user->email,
                'new_user_role' => $user->role
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur créé avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur création utilisateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'utilisateur')
                ->withInput();
        }
    }

    /**
     * Affichage détail utilisateur (AMÉLIORATION - votre AdminController n'avait pas de showUser)
     */
    public function show(User $user)
    {
        // Charger les relations selon votre modèle
        $user->load(['events', 'orders', 'commissions']);
        
        // Statistiques spécifiques à l'utilisateur
        $stats = [
            'events_count' => $user->events()->count(),
            'orders_count' => $user->orders()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'member_since' => $user->created_at->diffForHumans(),
        ];

        // Statistiques spécifiques selon le rôle
        if ($user->isPromoteur()) {
            $stats['total_revenue'] = $user->totalRevenue();
            $stats['pending_revenue'] = $user->pendingRevenue();
            $stats['tickets_sold'] = $user->totalTicketsSold();
        }

        // Activité récente selon le rôle
        $recentActivity = collect();
        
        if ($user->isPromoteur()) {
            $recentActivity = $user->events()
                ->latest()
                ->take(5)
                ->get()
                ->map(function($event) {
                    return [
                        'type' => 'event_created',
                        'message' => "Événement '{$event->title}' créé",
                        'date' => $event->created_at,
                        'icon' => 'fas fa-calendar-plus',
                        'color' => 'primary'
                    ];
                });
        } elseif ($user->isAcheteur()) {
            $recentActivity = $user->orders()
                ->latest()
                ->take(5)
                ->get()
                ->map(function($order) {
                    return [
                        'type' => 'order_placed',
                        'message' => "Commande #{$order->order_number} passée",
                        'date' => $order->created_at,
                        'icon' => 'fas fa-shopping-cart',
                        'color' => 'success'
                    ];
                });
        }

        return view('admin.users.show', compact('user', 'stats', 'recentActivity'));
    }

    /**
     * Formulaire d'édition (MIGRÉ depuis AdminController::editUser)
     */
    public function edit(User $user)
    {
        // Empêcher l'édition de son propre compte (sécurité)
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.show', $user)
                ->with('warning', 'Vous ne pouvez pas modifier votre propre compte depuis cette interface. Utilisez votre profil.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Mise à jour utilisateur (MIGRÉ depuis AdminController::updateUser)
     */
    public function update(Request $request, User $user)
    {
        // Empêcher la modification de son propre compte
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas modifier votre propre compte');
        }

        // Validation exacte de votre code existant avec ajout validation email unique
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,promoteur,acheteur',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        try {
            $data = $request->only(['name', 'email', 'role', 'phone']);
            
            // Gestion du mot de passe (si fourni)
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $oldData = $user->toArray(); // Pour log des changements
            $user->update($data);

            // Log des modifications
            \Log::info('Utilisateur modifié par admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'user_id' => $user->id,
                'changes' => array_diff_assoc($data, $oldData)
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur mis à jour avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour utilisateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Suppression utilisateur (MIGRÉ depuis AdminController::destroyUser)
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false, 
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 422);
        }

        // Vérifications métier avant suppression
        if ($user->isPromoteur() && $user->events()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet utilisateur car il a créé des événements'
            ], 422);
        }

        if ($user->orders()->where('payment_status', 'paid')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet utilisateur car il a des commandes payées'
            ], 422);
        }

        try {
            $userName = $user->name;
            $userEmail = $user->email;
            
            // Log avant suppression
            \Log::warning('Utilisateur supprimé par admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'deleted_user_id' => $user->id,
                'deleted_user_name' => $userName,
                'deleted_user_email' => $userEmail,
                'deleted_user_role' => $user->role
            ]);
            
            $user->delete();

            return response()->json([
                'success' => true, 
                'message' => "Utilisateur '{$userName}' supprimé avec succès"
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur suppression utilisateur: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * NOUVELLE MÉTHODE: Basculer le statut de vérification email
     */
    public function toggleEmailVerification(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut'
            ], 422);
        }

        try {
            $newStatus = $user->email_verified_at ? null : now();
            $user->update(['email_verified_at' => $newStatus]);
            
            $status = $newStatus ? 'vérifié' : 'non vérifié';
            
            // Log de l'action
            \Log::info('Statut email modifié par admin', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'new_status' => $status
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Utilisateur marqué comme {$status} avec succès",
                'new_status' => $newStatus ? 'verified' : 'unverified'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * NOUVELLE MÉTHODE: Actions en lot sur les utilisateurs
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:verify,unverify,delete',
            'users' => 'required|array',
            'users.*' => 'exists:users,id'
        ]);

        try {
            $userIds = collect($request->users)
                ->filter(function($id) {
                    return $id != auth()->id(); // Exclure son propre compte
                });

            $count = 0;

            switch ($request->action) {
                case 'verify':
                    $count = User::whereIn('id', $userIds)->update(['email_verified_at' => now()]);
                    $message = "{$count} utilisateur(s) vérifié(s)";
                    break;
                    
                case 'unverify':
                    $count = User::whereIn('id', $userIds)->update(['email_verified_at' => null]);
                    $message = "{$count} utilisateur(s) marqué(s) comme non vérifiés";
                    break;
                    
                case 'delete':
                    // Vérifier qu'aucun promoteur n'a d'événements
                    $promotersWithEvents = User::whereIn('id', $userIds)
                        ->where('role', 'promoteur')
                        ->whereHas('events')
                        ->count();
                        
                    if ($promotersWithEvents > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Impossible de supprimer des promoteurs qui ont créé des événements'
                        ], 422);
                    }
                    
                    $count = User::whereIn('id', $userIds)->delete();
                    $message = "{$count} utilisateur(s) supprimé(s)";
                    break;
            }

            // Log de l'action en lot
            \Log::info('Action en lot sur utilisateurs', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'count' => $count,
                'user_ids' => $userIds->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur action en lot: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action en lot'
            ], 500);
        }
    }

    /**
     * NOUVELLE MÉTHODE: Export des utilisateurs
     */
    public function export(Request $request)
    {
        try {
            $users = User::when($request->role, function($query, $role) {
                    return $query->where('role', $role);
                })
                ->when($request->status, function($query, $status) {
                    if ($status === 'verified') {
                        return $query->whereNotNull('email_verified_at');
                    } elseif ($status === 'unverified') {
                        return $query->whereNull('email_verified_at');
                    }
                })
                ->get();

            $csvData = [];
            $csvData[] = ['Nom', 'Email', 'Rôle', 'Téléphone', 'Statut Email', 'Date Inscription'];

            foreach ($users as $user) {
                $csvData[] = [
                    $user->name,
                    $user->email,
                    ucfirst($user->role),
                    $user->phone ?? 'N/A',
                    $user->email_verified_at ? 'Vérifié' : 'Non vérifié',
                    $user->created_at->format('d/m/Y H:i')
                ];
            }

            $filename = 'utilisateurs_' . date('Y_m_d_H_i_s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ["\xEF\xBB\xBF"]); // BOM UTF-8
                
                foreach ($csvData as $row) {
                    fputcsv($file, $row, ';'); // Point-virgule pour Excel français
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Erreur export utilisateurs: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export');
        }
    }
}