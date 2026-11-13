<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Services\Stats\CommissionStats;
use App\Services\Stats\PlatformStats;
use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;

class UserController extends Controller
{
    use ResolvesStatsPeriod;

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
        $period = $this->statsPeriod($request, 'admin.users.index');

        // Mêmes filtres et même période que l'export (filteredQuery)
        $query = $this->filteredQuery($request, $period);

        $users = $query->latest()
                      ->paginate(20)
                      ->appends($request->query());

        // Chiffres : service Stats (totaux instantanés ; « nouveaux » = inscrits dans la période affichée)
        $stats = app(PlatformStats::class)->users($period);

        return view('admin.users.index', compact('users', 'stats', 'period'));
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
            ]);

            // Auto-vérifier les comptes créés par admin. forceFill : email_verified_at n'est pas $fillable
            // (sans cela la valeur était ignorée en silence et le compte restait « non vérifié »).
            $user->forceFill(['email_verified_at' => now()])->save();

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
        $all = DatePeriod::all();

        // Chiffres (toutes dates) : services Stats, mêmes définitions que le reste de l'admin
        $stats = [
            'events_count' => $user->events()->count(),
            'orders_count' => $user->orders()->count(),
            'member_since' => $user->created_at->diffForHumans(),
        ];

        // Acheteur : encaissé sur ses commandes payées (les commandes en attente / remboursées n'y entrent pas)
        $stats['total_spent'] = app(SalesStats::class)->collected(Scope::buyer($user->id), $all);

        // Promoteur : ventes hors frais, net après commission (à verser / versé), billets vendus
        if ($user->isPromoteur()) {
            $scope = Scope::promoter($user->id);
            $stats['total_sales'] = app(SalesStats::class)->gmv($scope, $all);
            $stats['pending_revenue'] = app(CommissionStats::class)->pendingPayout($scope, $all);
            $stats['paid_out'] = app(CommissionStats::class)->paidOut($scope, $all);
            $stats['tickets_sold'] = app(TicketStats::class)->sold($scope, $all);
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
            // forceFill : email_verified_at n'est pas $fillable (update() l'ignorait et répondait « succès »).
            $user->forceFill(['email_verified_at' => $newStatus])->save();
            
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
                    // Un à un (et non en requête de masse) pour que le journal d'activité voie chaque changement
                    $count = 0;
                    foreach (User::whereIn('id', $userIds)->get() as $target) {
                        $target->forceFill(['email_verified_at' => now()])->save(); // forceFill : email_verified_at n'est pas $fillable (comme l'ancienne requête de masse)
                        $count++;
                    }
                    $message = "{$count} utilisateur(s) vérifié(s)";
                    break;
                    
                case 'unverify':
                    $count = 0;
                    foreach (User::whereIn('id', $userIds)->get() as $target) {
                        $target->forceFill(['email_verified_at' => null])->save();
                        $count++;
                    }
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
     * Utilisateurs filtrés : mêmes filtres (rôle, recherche, statut e-mail) et même période (inscriptions dans la
     * période, DatePeriod) pour la liste, les cartes ET l'export.
     */
    private function filteredQuery(Request $request, DatePeriod $period)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        if ($period->start !== null) {
            $query->where('created_at', '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $query->where('created_at', '<=', $period->endForDb());
        }

        return $query;
    }

    /**
     * Export CSV des utilisateurs : mêmes filtres et même période que l'écran. L'e-mail reste COMPLET parce que
     * l'écran admin des utilisateurs (users/index.blade.php) l'affiche en entier, comme le téléphone ; jamais de
     * mot de passe ni de jeton (colonnes explicites, pas de toArray()).
     */
    public function export(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.users.index');
        $query = $this->filteredQuery($request, $period)->select(['id', 'name', 'email', 'role', 'phone', 'email_verified_at', 'created_at']);

        return \App\Support\CsvExport::response(
            'utilisateurs-'.now()->format('Y-m-d').'.csv',
            ['Nom', 'Email', 'Rôle', 'Téléphone', 'Statut Email', 'Date Inscription'],
            function (callable $emit) use ($query) {
                $query->chunkByIdDesc(500, function ($users) use ($emit) {
                    foreach ($users as $user) {
                        $emit([
                            $user->name,
                            $user->email,
                            ucfirst((string) $user->role),
                            $user->phone ?: 'N/A',
                            $user->email_verified_at ? 'Vérifié' : 'Non vérifié',
                            $user->created_at->format('d/m/Y H:i'),
                        ]);
                    }
                });
            }
        );
    }

/**
 * Envoyer un lien de reset password pour un utilisateur (action admin)
 */
public function resetPassword(Request $request, User $user)
{
    try {
        // Générer un token de reset
        $token = \Illuminate\Support\Str::random(60);
        
        // Sauvegarder le token
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => \Hash::make($token),
                'created_at' => now()
            ]
        );
        
        // Envoyer l'email
        \Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($token, $user->email));
        
        Log::info('Reset password envoyé par admin', [
            'admin_id' => auth()->id(),
            'target_user_id' => $user->id,
            'target_email' => $user->email
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Email de réinitialisation envoyé avec succès !'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur reset password admin', [
            'admin_id' => auth()->id(),
            'target_user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi'
        ], 500);
    }
}
}