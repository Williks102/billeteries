<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\{Event, Order, Commission, Ticket};
use Carbon\Carbon;

/**
 * Profil du promoteur (singleton : show, update) + avatar.
 */
class ProfileController extends Controller
{
    /**
     * Profil du promoteur
     */
    public function show()
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
    public function update(Request $request)
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
}
