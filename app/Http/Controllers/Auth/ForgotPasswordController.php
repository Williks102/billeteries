<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        // Limitation par IP et par e-mail (config payments.password_reset_*), 429 en français
        $this->middleware(\App\Http\Middleware\PasswordResetThrottle::class.':forgot')->only('sendResetLinkEmail');
    }

    /**
     * Afficher le formulaire de demande de reset
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Envoyer le lien de reset par email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // L'envoi (synchrone quand le compte existe) se fait APRÈS l'envoi de la réponse : le temps de réponse ne
        // distingue plus un compte existant d'un compte inconnu (pas d'énumération par chronométrage).
        $credentials = $this->credentials($request);
        $emailHash = hash_hmac('sha256', mb_strtolower(trim((string) $request->input('email'))), (string) config('app.key'));
        $broker = $this->broker();

        app()->terminating(function () use ($broker, $credentials, $emailHash) {
            try {
                $response = $broker->sendResetLink($credentials);
                // Journal sans donnée personnelle : ni e-mail ni IP en clair (empreinte HMAC seulement)
                Log::info('Demande de réinitialisation de mot de passe', [
                    'email_hash' => $emailHash,
                    'sent' => $response === Password::RESET_LINK_SENT,
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur lors de la demande de réinitialisation de mot de passe', ['email_hash' => $emailHash]);
            }
        });

        // Réponse NEUTRE : identique que le compte existe ou non (pas d'énumération de comptes).
        return back()->with('status', 'Si un compte correspond à cette adresse, un lien de réinitialisation vient de lui être envoyé.');
    }
}
