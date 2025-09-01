<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Redirection après reset réussi
     */
    protected $redirectTo = '/dashboard';

    /**
     * Afficher le formulaire de reset
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        Log::info("Tentative reset password", [
            'email' => $request->email,
            'token' => substr($request->token, 0, 10) . '...',
            'ip' => $request->ip()
        ]);

        // Réinitialiser le mot de passe
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($response == \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            Log::info("Password reset réussi", [
                'email' => $request->email
            ]);
        } else {
            Log::warning("Échec password reset", [
                'email' => $request->email,
                'response' => $response
            ]);
        }

        return $response == \Illuminate\Support\Facades\Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Redirection personnalisée après reset
     */
    protected function sendResetResponse(Request $request, $response)
    {
        $user = \App\Models\User::where('email', $request->email)->first();
        
        if ($user) {
            // Redirection selon le rôle
            $redirectTo = match($user->role) {
                'admin' => route('admin.dashboard'),
                'promoteur' => route('promoteur.dashboard'), 
                'acheteur' => route('acheteur.dashboard'),
                default => '/dashboard'
            };
            
            return redirect($redirectTo)->with('status', 'Votre mot de passe a été réinitialisé avec succès !');
        }
        
        return redirect($this->redirectPath())->with('status', trans($response));
    }

    /**
     * Messages de validation personnalisés
     */
    protected function validationErrorMessages()
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'token.required' => 'Le token de réinitialisation est obligatoire.'
        ];
    }
}
