<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Nettoyage du HTML saisi par un administrateur (contenu des pages du CMS), avant enregistrement ET à l'affichage
 * (contenu déjà stocké). Liste d'autorisation stricte : p, br, strong, em, u, ul, ol, li, h2-h4, blockquote, hr et a.
 * Les balises courantes hors liste (div, h1, span...) sont retirées en gardant leur texte. Liens : https:// ou mailto: seulement, rel="noopener noreferrer" forcé. Ni script, style, iframe, formulaire,
 * image, ni attribut d'événement, ni `javascript:`.
 */
class HtmlSanitizer
{
    private static ?SymfonySanitizer $instance = null;

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::instance()->sanitize($html);
    }

    private static function instance(): SymfonySanitizer
    {
        if (self::$instance === null) {
            $config = (new HtmlSanitizerConfig())
                ->allowElement('p')->allowElement('br')->allowElement('strong')->allowElement('em')
                ->allowElement('u')->allowElement('ul')->allowElement('ol')->allowElement('li')
                ->allowElement('h2')->allowElement('h3')->allowElement('h4')
                ->allowElement('blockquote')->allowElement('hr')
                ->allowElement('a', ['href'])
                ->allowLinkSchemes(['https', 'mailto'])
                ->forceHttpsUrls(false)
                ->allowRelativeLinks(false)
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->dropElement('script')->dropElement('style')->dropElement('iframe')
                ->dropElement('form')->dropElement('object')->dropElement('embed')
                ->withMaxInputLength(500000);

            // Balises courantes hors liste : retirées en GARDANT leur texte (le reste, inconnu, est supprimé avec son contenu).
            foreach (['div', 'span', 'h1', 'h5', 'h6', 'b', 'i', 'section', 'article', 'header', 'footer', 'main', 'nav',
                'table', 'thead', 'tbody', 'tr', 'td', 'th', 'small', 'center', 'pre', 'code', 'font', 'label', 'dl', 'dt', 'dd'] as $tag) {
                $config = $config->blockElement($tag);
            }

            self::$instance = new SymfonySanitizer($config);
        }

        return self::$instance;
    }
}
