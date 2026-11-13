<?php

namespace Tests\Feature\Security;

use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Contenu des pages (CMS) : HTML nettoyé à l'enregistrement ET à l'affichage (contenu hérité compris). */
class HtmlSanitizerTest extends TestCase
{
    use BuildsOrderSchema;

    private const DIRTY = '<p onclick="x()">Bonjour</p><script>alert(1)</script><iframe src="https://evil.test"></iframe>'
        .'<style>body{display:none}</style><img src="https://evil.test/a.png" onerror="alert(2)">'
        .'<a href="javascript:alert(3)">piege</a><form action="https://evil.test"><input name="x"></form>';

    private function assertClean(string $html): void
    {
        foreach (['<script', 'alert(', 'onerror', 'onclick', 'javascript:', '<iframe', '<style', '<img', '<form', '<input'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $html);
        }
    }

    public function test_dangerous_markup_is_removed(): void
    {
        $clean = HtmlSanitizer::clean(self::DIRTY);

        $this->assertClean($clean);
        $this->assertStringContainsString('<p>Bonjour</p>', $clean);
        $this->assertStringContainsString('piege', $clean);
    }

    public function test_allowed_tags_are_kept(): void
    {
        $html = '<h2>T</h2><h3>S</h3><h4>SS</h4><p>a<br>b <strong>g</strong> <em>i</em> <u>s</u></p><ul><li>1</li></ul>'
            .'<ol><li>2</li></ol><blockquote>q</blockquote><hr>';
        $clean = HtmlSanitizer::clean($html);

        foreach (['<h2>T</h2>', '<h3>S</h3>', '<h4>SS</h4>', '<br />', '<strong>g</strong>', '<em>i</em>', '<u>s</u>',
            '<ul><li>1</li></ul>', '<ol><li>2</li></ol>', '<blockquote>q</blockquote>', '<hr />'] as $expected) {
            $this->assertStringContainsString($expected, $clean);
        }
    }

    public function test_links_are_https_or_mailto_only_with_rel(): void
    {
        $clean = HtmlSanitizer::clean('<a href="https://ok.test/x">a</a><a href="mailto:contact@example.test">b</a>'
            .'<a href="http://plain.test">c</a><a href="javascript:alert(1)">d</a><a href="data:text/html,x">e</a>');

        $this->assertStringContainsString('href="https://ok.test/x" rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('mailto:contact', $clean);
        $this->assertStringNotContainsString('http://plain.test', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('data:', $clean);
    }

    public function test_common_unlisted_tags_keep_their_text_and_null_is_empty(): void
    {
        $this->assertSame('titretexte', HtmlSanitizer::clean('<div><h1>titre</h1>texte</div>'));
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('  '));
    }

    private function pagesSchema(): void
    {
        $this->buildOrderSchema();
        Schema::dropIfExists('pages'); // la table minimale du schéma de test n'a pas de colonne content
        (include base_path('database/migrations/2025_08_01_125848_create_pages_table.php'))->up();
    }

    public function test_admin_store_and_update_sanitize_content(): void
    {
        $this->pagesSchema();
        $admin = $this->makeUser(10, 'admin');

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Page test', 'slug' => 'page-test', 'content' => self::DIRTY, 'template' => 'default',
        ])->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'page-test')->firstOrFail();
        $this->assertClean($page->content);
        $this->assertStringContainsString('Bonjour', $page->content);

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'Page test', 'slug' => 'page-test', 'content' => '<p>ok</p>'.self::DIRTY, 'template' => 'default',
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertClean($page->fresh()->content);
    }

    public function test_legacy_stored_content_is_cleaned_at_display(): void
    {
        $this->pagesSchema();
        // Contenu hérité inséré directement en base (sans passer par le modèle) : l'affichage doit le nettoyer.
        DB::table('pages')->insert([
            'title' => 'Ancienne', 'slug' => 'ancienne', 'content' => self::DIRTY.'<p>VISIBLE</p>', 'template' => 'default',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = $this->get('/page/ancienne')->assertOk()->getContent();
        $this->assertStringContainsString('VISIBLE', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('evil.test', $html);
    }
}
