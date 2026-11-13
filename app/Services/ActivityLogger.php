<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Écriture du journal d'activité.
 *
 * - NE LÈVE JAMAIS : un échec est tracé par Log::warning (sans donnée personnelle) et n'arrête jamais
 *   une action métier ni un paiement ;
 * - écrit APRÈS le commit quand il est appelé dans une transaction (DB::afterCommit) : une action annulée
 *   n'est jamais journalisée ;
 * - confidentialité : uniquement des identifiants et des valeurs métier. Les clés sensibles sont retirées
 *   des propriétés par défense en profondeur ; ne jamais y mettre e-mail, téléphone, nom, jeton, mot de passe.
 */
class ActivityLogger
{
    /** Fragments de clés interdits dans `properties` (comparaison en minuscules). */
    private const FORBIDDEN_KEY_PARTS = [
        'email', 'phone', 'password', 'token', 'secret', 'api_key', 'apikey', 'name', 'address',
        'mail', 'mobile', 'iban', 'card', 'jwt', 'cvv',
    ];

    /**
     * Fragments trop courts pour une recherche par sous-chaîne (« ip » est dans « description », « pin » dans
     * « shipping ») : retirés seulement s'ils forment un mot de la clé (user_ip, clientIp, tel, pin_code).
     */
    private const FORBIDDEN_KEY_WORDS = ['tel', 'ip', 'pin'];

    /**
     * @param  array<string,mixed>  $properties  valeurs avant/après (ex. ['price' => ['from' => 1000, 'to' => 1500]])
     * @param  bool  $asSystem  force l'acteur « système » (confirmation automatique : démo, commande gratuite)
     */
    public static function record(string $action, ?Model $subject, string $description, array $properties = [], ?int $eventId = null, bool $asSystem = false): void
    {
        try {
            $actor = $asSystem ? null : Auth::user();

            $payload = [
                'user_id' => $actor?->getAuthIdentifier(),
                'actor_role' => $actor ? (string) ($actor->role ?? '') ?: null : 'system',
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'event_id' => $eventId ?? self::eventIdOf($subject),
                'description' => mb_substr($description, 0, 255),
                'properties' => $properties === [] ? null : self::scrub($properties),
                'ip_fingerprint' => self::fingerprint(),
                'created_at' => now(),
            ];

            DB::afterCommit(function () use ($payload) {
                try {
                    ActivityLog::create($payload);
                } catch (\Throwable $e) {
                    self::warn($payload['action'] ?? '?', $e);
                }
            });
        } catch (\Throwable $e) {
            self::warn($action, $e);
        }
    }

    private static function eventIdOf(?Model $subject): ?int
    {
        if ($subject === null) {
            return null;
        }
        if ($subject instanceof \App\Models\Event) {
            return $subject->getKey();
        }

        $id = $subject->getAttribute('event_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /** Empreinte HMAC de l'IP de la requête HTTP (réutilise CheckoutService::ipHash) ; null hors requête HTTP. */
    private static function fingerprint(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $ip = request()->ip();

        return $ip ? CheckoutService::ipHash($ip) : null;
    }

    /** @param  array<string,mixed>  $properties */
    private static function scrub(array $properties): array
    {
        $clean = [];
        foreach ($properties as $key => $value) {
            $lower = strtolower((string) $key);
            $words = array_map('strtolower', preg_split('/[^A-Za-z0-9]+|(?<=[a-z])(?=[A-Z])/', (string) $key, -1, PREG_SPLIT_NO_EMPTY) ?: []);
            if (array_intersect(self::FORBIDDEN_KEY_WORDS, $words) !== []) {
                continue;
            }
            foreach (self::FORBIDDEN_KEY_PARTS as $part) {
                if (str_contains($lower, $part)) {
                    continue 2;
                }
            }
            $clean[$key] = is_array($value) ? self::scrub($value) : $value;
        }

        return $clean;
    }

    private static function warn(string $action, \Throwable $e): void
    {
        try {
            Log::warning('Journal d\'activité : écriture impossible', ['action' => $action, 'exception' => get_class($e)]);
        } catch (\Throwable) {
            // rien : la journalisation ne doit jamais casser l'appelant
        }
    }
}
