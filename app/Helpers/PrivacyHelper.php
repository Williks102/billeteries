<?php

namespace App\Helpers;

class PrivacyHelper
{
    /** « jean.dupont@exemple.ci » => « je***@exemple.ci » (affichage en liste ; l'adresse complète reste dans la fiche). */
    public static function maskEmail(?string $email): string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return '—';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 2).'***@'.$domain;
    }
}
