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

        // Journal sans donnée personnelle : ni e-mail ni IP en clair (empreintes HMAC seulement)
        $response = $this->broker()->sendResetLink($this->credentials($request));

        Log::info('Demande de réinitialisation de mot de passe', [
            'email_hash' => hash_hmac('sha256', mb_strtolower(trim((string) $request->input('email'))), (string) config('app.key')),
            'sent' => $response === Password::RESET_LINK_SENT,
        ]);

        // Réponse NEUTRE : identique que le compte existe ou non (pas d'énumération de comptes).
        return back()->with('status', 'Si un compte correspond à cette adresse, un lien de réinitialisation vient de lui être envoyé.');
    }
}
