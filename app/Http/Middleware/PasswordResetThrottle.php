<?php

namespace App\Http\Middleware;

use App\Services\CheckoutService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limitation de débit « mot de passe oublié » / « réinitialisation » : par IP ET par adresse e-mail.
 *
 * - les clés de cache ne contiennent jamais l'adresse ni l'IP en clair : HMAC avec la clé de l'application ;
 * - la limite par e-mail est calculée pour le COUPLE (e-mail, IP) : un tiers ne peut pas, depuis une autre IP,
 *   épuiser le quota de la victime et lui interdire de demander un lien ou de soumettre son vrai jeton.
 *   Elle compte chaque demande, que le compte existe ou non : le 429 ne révèle donc rien sur l'existence d'un compte.
 *   Le « mail-bombing » d'une victime depuis plusieurs IP reste borné par le délai de 60 s du broker Laravel
 *   (`auth.passwords.users.throttle`) : au plus un e-mail par minute et par compte ;
 * - usage : PasswordResetThrottle::class.':forgot' (demande de lien) ou ':reset' (soumission du nouveau mot de passe).
 */
class PasswordResetThrottle
{
    public const MESSAGE = 'Trop de tentatives. Patientez quelques minutes avant de réessayer.';

    public function handle(Request $request, Closure $next, string $scope = 'forgot'): Response
    {
        $retryAfter = self::attempt($request, $scope);
        if ($retryAfter !== null) {
            return $this->tooMany($request, $retryAfter);
        }

        return $next($request);
    }

    /**
     * Contrôle puis compte une demande (IP + couple e-mail/IP). Retourne null si elle est autorisée,
     * sinon le nombre de secondes d'attente. Réutilisé par l'inscription d'un e-mail « invité » (activation).
     */
    public static function attempt(Request $request, string $scope = 'forgot'): ?int
    {
        $ipKey = 'pwreset:'.$scope.':ip:'.CheckoutService::ipHash((string) $request->ip());
        $perMinute = max(1, (int) config('payments.password_reset_throttle_per_minute', 5));

        $keys = [[$ipKey, $perMinute, 60]];

        $email = $request->input('email');
        if (is_string($email) && trim($email) !== '') {
            $normalized = mb_strtolower(trim($email));
            $max = $scope === 'reset'
                ? (int) config('payments.password_reset_email_reset_max', 5)
                : (int) config('payments.password_reset_email_max', 3);
            $decay = max(1, (int) config('payments.password_reset_email_window_minutes', 10)) * 60;
            $keys[] = ['pwreset:'.$scope.':mail:'.hash_hmac('sha256', $normalized.'|'.(string) $request->ip(), (string) config('app.key')), max(1, $max), $decay];
        }

        // Contrôle de TOUTES les limites avant de compter : une demande refusée ne consomme pas l'autre compteur.
        foreach ($keys as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return RateLimiter::availableIn($key);
            }
        }
        foreach ($keys as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    /** 429 en français (la page d'erreur par défaut du framework est en anglais et ignore le message). */
    private function tooMany(Request $request, int $retryAfter): Response
    {
        $headers = ['Retry-After' => max(1, $retryAfter)];

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 429, $headers);
        }

        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Trop de tentatives</title></head><body style="font-family:sans-serif;max-width:32rem;margin:4rem auto;padding:0 1rem">'
            .'<h1 style="font-size:1.5rem">Trop de tentatives</h1><p>'.e(self::MESSAGE).'</p>'
            .'<p><a href="'.e(route('login')).'">Retour à la connexion</a></p></body></html>';

        return response($html, 429, $headers);
    }
}
