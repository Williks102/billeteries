<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Vérifie la disponibilité d'un email (extrait de routes/web.php, corps inchangé).
 * Route : POST /api/check-email (throttle 60/min).
 */
class EmailAvailabilityController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            
            $exists = \App\Models\User::where('email', $request->email)
                                      ->where('is_guest', false) // Exclure les invités
                                      ->exists();
            
            return response()->json([
                'success' => true,
                'available' => !$exists,
                'exists' => $exists
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Erreur API check-email: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de vérification'
            ], 500);
        }    }
}

