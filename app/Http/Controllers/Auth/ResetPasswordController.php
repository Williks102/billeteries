<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function __construct()
    {
        // Limitation par IP et par e-mail (config payments.password_reset_*), 429 en français
        $this->middleware(\App\Http\Middleware\PasswordResetThrottle::class.':reset')->only('reset');
    }

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

        // Réinitialiser le mot de passe
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        // Journal sans donnée personnelle (ni e-mail, ni jeton, ni IP)
        Log::info('Réinitialisation de mot de passe', ['succes' => $response == \Illuminate\Support\Facades\Password::PASSWORD_RESET]);

        return $response == \Illuminate\Support\Facades\Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Mot de passe défini via un lien valide (preuve de possession de l'e-mail). Pour un compte INVITÉ, c'est
     * l'activation : le compte devient un vrai compte (rôle inchangé, nom et téléphone conservés) et ses
     * commandes, déjà reliées par orders.user_id, apparaissent dans l'espace acheteur. Autre compte : inchangé.
     */
    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);
        $user->setRememberToken(\Illuminate\Support\Str::random(60));

        $activated = (bool) $user->is_guest;
        if ($activated) {
            $user->is_guest = false;
            $user->guest_converted_at = now();
            $user->email_verified_at = now();
        }

        $user->save();

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        $this->guard()->login($user);
        if ($activated && request()->hasSession()) {
            request()->session()->regenerate();
        }
    }

    /**
     * Échec NEUTRE : même message que l'e-mail existe ou non (pas d'énumération de comptes).
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        $message = 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau.';

        if ($request->wantsJson()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => [$message]]);
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
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
