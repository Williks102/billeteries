<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Formulaire des types de billets du promoteur (create + edit) : un seul document, accès réservé
 * au propriétaire, champs mobiles (type / inputmode), garde-fou « pas de largeurs fixes ».
 */
class PromoteurTicketFormMobileTest extends TestCase
{
    use BuildsOrderSchema;

    private const DIR = 'resources/views/promoteur/events/tickets/';

    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        $this->makeUser(2, 'promoteur');   // propriétaire de l'événement 1
        $this->makeUser(3, 'promoteur');   // autre promoteur
        $this->typeId = $this->ticketType(100, 7)->id;
        DB::table('ticket_types')->where('id', $this->typeId)->update(['event_id' => 1, 'name' => 'Billet VIP']);
    }

    private function createUrl(): string
    {
        return route('promoteur.events.tickets.create', 1);
    }

    private function editUrl(): string
    {
        return route('promoteur.events.tickets.edit', [1, $this->typeId]);
    }

    public function test_owner_gets_create_and_edit_pages(): void
    {
        $owner = \App\Models\User::find(2);
        $this->actingAs($owner)->get($this->createUrl())->assertOk();
        $this->actingAs($owner)->get($this->editUrl())->assertOk()
            ->assertSee('Billet VIP')
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_other_promoter_is_forbidden(): void
    {
        $other = \App\Models\User::find(3);
        $this->actingAs($other)->get($this->createUrl())->assertForbidden();
        $this->actingAs($other)->get($this->editUrl())->assertForbidden();
    }

    public function test_create_page_is_a_single_document(): void
    {
        $html = $this->actingAs(\App\Models\User::find(2))->get($this->createUrl())->getContent();
        $this->assertSame(1, substr_count($html, '<html'));
        $this->assertSame(1, substr_count($html, '</html>'));

        foreach (['create', 'edit', '_form', '_fields'] as $view) {
            $src = file_get_contents(base_path(self::DIR.$view.'.blade.php'));
            $this->assertLessThanOrEqual(1, substr_count($src, '@extends('), "$view : plusieurs @extends");
        }
    }

    public function test_fields_have_mobile_friendly_types(): void
    {
        $html = $this->actingAs(\App\Models\User::find(2))->get($this->createUrl())->getContent();

        $this->assertMatchesRegularExpression('/<input type="text" id="tt-0-name" name="ticket_types\[0\]\[name\]"/', $html);
        foreach (['price' => 'price', 'quantity' => 'quantity_available', 'max' => 'max_per_order'] as $id => $name) {
            $this->assertMatchesRegularExpression(
                '/<input type="number" id="tt-0-'.$id.'" name="ticket_types\[0\]\['.$name.'\]"[^>]*inputmode="numeric"[^>]*pattern="\[0-9\]\*"[^>]*autocomplete="off"/s',
                $html, "champ $name"
            );
        }
        foreach (['sale_start_date', 'sale_end_date'] as $name) {
            $this->assertMatchesRegularExpression('/<input type="date" id="tt-0-(start|end)" name="ticket_types\[0\]\['.$name.'\]"/', $html);
        }

        $edit = $this->actingAs(\App\Models\User::find(2))->get($this->editUrl())->getContent();
        $this->assertStringContainsString('name="price"', $edit);
        $this->assertMatchesRegularExpression('/name="quantity_available" required min="7"[^>]*inputmode="numeric"/s', $edit);
        $this->assertMatchesRegularExpression('/name="max_per_order"[^>]*inputmode="numeric"/s', $edit);
    }

    public function test_validation_errors_are_linked_to_their_field(): void
    {
        $html = $this->actingAs(\App\Models\User::find(2))
            ->from($this->createUrl())
            ->followingRedirects()
            ->post(route('promoteur.events.tickets.store', 1), ['ticket_types' => [0 => ['name' => '', 'price' => '-1']]])
            ->getContent();

        $this->assertStringContainsString('id="tt-0-name-error"', $html);
        $this->assertStringContainsString('aria-describedby="tt-0-name-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_form_sources_have_no_fixed_pixel_widths_and_use_full_width_columns(): void
    {
        foreach (['create', 'edit', '_form', '_fields'] as $view) {
            $src = file_get_contents(base_path(self::DIR.$view.'.blade.php'));
            // Pas de largeur / hauteur fixe en px (max-width de conteneur et media queries autorisés).
            $this->assertDoesNotMatchRegularExpression('/(?<![-\w])(width)\s*:\s*\d+px/', $src, "$view : largeur fixe");
            $this->assertDoesNotMatchRegularExpression('/\bwidth="\d+"/', $src, "$view : attribut width fixe");
        }
        $fields = file_get_contents(base_path(self::DIR.'_fields.blade.php'));
        $this->assertStringContainsString('col-12', $fields);
        $this->assertStringNotContainsString('col-md-', $fields); // colonne unique sous 576 px
        $this->assertStringContainsString('font-size: 16px', file_get_contents(base_path(self::DIR.'_form.blade.php')));
    }
}
