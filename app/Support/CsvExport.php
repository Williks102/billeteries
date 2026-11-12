<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV unique des écrans de statistiques : BOM UTF-8, séparateur « ; » (Excel FR), fputcsv (quotage correct),
 * neutralisation des formules sur toute cellule texte (CsvSafe), largeur de ligne = largeur de l'en-tête,
 * écriture en flux (le contrôleur lit par paquets : pas de fichier entier en mémoire).
 */
final class CsvExport
{
    public const SEPARATOR = ';';

    /**
     * @param  list<string>  $headings
     * @param  callable(callable(list<mixed>): void): void  $produce  reçoit « emit » : emit($ligne) écrit une ligne
     */
    public static function response(string $filename, array $headings, callable $produce): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $produce) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings, self::SEPARATOR);

            $width = count($headings);
            $produce(function (array $row) use ($out, $width) {
                if (count($row) !== $width) {
                    throw new \LogicException('Ligne d\'export de largeur incorrecte.');
                }
                fputcsv($out, array_map([self::class, 'cell'], array_values($row)), self::SEPARATOR);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /** Nombres tels quels (un montant négatif reste un nombre) ; tout le reste est du texte neutralisé. */
    public static function cell(mixed $value): int|float|string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return CsvSafe::cell($value);
    }
}
