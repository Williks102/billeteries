<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Normalise APP_KEY au démarrage du conteneur (docker/entrypoint.sh), pour qu'une clé saisie ou générée depuis un
 * téléphone (par exemple la valeur générée par Render, sans préfixe « base64: ») soit acceptée par Laravel
 * (AES-256-CBC : 32 octets).
 *
 * Formes acceptées (la valeur n'est JAMAIS affichée, sauf avec --print, destiné à être capturé par le shell) :
 *  - « base64:<32 octets en base64> » : inchangée ;
 *  - 32 caractères bruts : inchangée (Laravel les accepte telles quelles) ;
 *  - base64 sans préfixe (avec ou sans « = », alphabet standard ou URL-safe) décodé en exactement 32 octets :
 *    le préfixe « base64: » est ajouté.
 * Toute autre forme : message d'erreur sans la valeur, code de sortie 1.
 */
class NormalizeAppKey extends Command
{
    private const KEY_BYTES = 32;

    protected $signature = 'app:normalize-key {--print : Écrire la clé normalisée sur la sortie standard (à capturer, jamais à journaliser)}';

    protected $description = 'Vérifie APP_KEY (32 octets) et ajoute le préfixe base64: si nécessaire, sans jamais afficher la valeur';

    public function handle(): int
    {
        $result = self::normalize((string) config('app.key'));

        if ($result['key'] === null) {
            $this->getOutput()->getErrorStyle()->writeln('<error>APP_KEY invalide : '.$result['reason'].'</error>');

            return self::FAILURE;
        }

        if ($this->option('print')) {
            $this->getOutput()->write($result['key'], false, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
        } else {
            $this->getOutput()->getErrorStyle()->writeln('APP_KEY : '.$result['reason']);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{key: string|null, reason: string} key = valeur utilisable par Laravel, ou null si invalide
     */
    public static function normalize(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return ['key' => null, 'reason' => 'la variable est absente ou vide.'];
        }

        if (str_starts_with($value, 'base64:')) {
            return self::decodesToKeyLength(substr($value, 7)) !== null
                ? ['key' => $value, 'reason' => 'format base64: correct, inchangée.']
                : ['key' => null, 'reason' => 'le préfixe base64: est présent mais la valeur ne se décode pas en exactement '.self::KEY_BYTES.' octets.'];
        }

        if (strlen($value) === self::KEY_BYTES) {
            return ['key' => $value, 'reason' => '32 caractères bruts, acceptée telle quelle.'];
        }

        $decoded = self::decodesToKeyLength($value);
        if ($decoded !== null) {
            return ['key' => 'base64:'.base64_encode($decoded), 'reason' => 'préfixe base64: ajouté (la valeur se décode en 32 octets).'];
        }

        return ['key' => null, 'reason' => 'attendu : « base64: » suivi de 32 octets encodés en base64 (44 caractères), ou 32 caractères bruts. Valeur non affichée.'];
    }

    /** @return string|null les 32 octets décodés, ou null */
    private static function decodesToKeyLength(string $encoded): ?string
    {
        $encoded = strtr(rtrim($encoded, '='), '-_', '+/');

        if ($encoded === '' || preg_match('/^[A-Za-z0-9+\/]+$/', $encoded) !== 1) {
            return null;
        }

        $decoded = base64_decode($encoded.str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);

        return $decoded !== false && strlen($decoded) === self::KEY_BYTES ? $decoded : null;
    }
}
