<?php

namespace App\Support;

class CsvSafe
{
    /** Neutralise l'injection de formules (tableur) : apostrophe en préfixe d'un texte commençant par = + - @ tab ou CR. */
    public static function cell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
