<?php

// ============================================
// 1. Modifier app/Http/Controllers/Auth/ForgotPasswordController.php
// ============================================

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

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

        Log::info("Demande reset password", [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Envoyer le lien de reset
        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        if ($response == \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            Log::info("Email reset password envoyé", [
                'email' => $request->email
            ]);
        } else {
            Log::warning("Échec envoi reset password", [
                'email' => $request->email,
                'response' => $response
            ]);
        }

        return $response == \Illuminate\Support\Facades\Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
    }
}
