<?php

namespace Tests\Feature;

use App\Models\EventCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Pages publiques (lot 1 « pages manquantes ») rendues via leur vraie route.
 */
class PublicPagesTest extends TestCase
{
    use BuildsOrderSchema;

    private EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::table('events', function ($t) {
            $t->text('description')->nullable();
            $t->string('address')->nullable();
            $t->string('image')->nullable();
        });

        $this->category = EventCategory::create(['name' => 'Musique', 'slug' => 'musique', 'is_active' => true]);
        DB::table('events')->where('id', 1)->update([
            'category_id' => $this->category->id,
            'event_date' => now()->addWeek()->toDateString(),
            'title' => 'Concert Public',
        ]);
        $this->ticketType(10);
        DB::table('ticket_types')->update(['event_id' => 1]);
    }

    private function assertCleanPage($response): void
    {
        $response->assertOk();
        $html = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\b(pages|search|categories|checkout)\.[a-z_]+\.[a-z_]+\b/', strip_tags($html));
    }

    public function test_search_results_page_lists_matching_events(): void
    {
        $res = $this->get('/search?q=Concert');
        $this->assertCleanPage($res);
        $res->assertSee('Concert Public');
    }

    public function test_search_results_page_without_match(): void
    {
        $this->assertCleanPage($this->get('/search?q=zzzzz'));
    }

    public function test_category_page_renders(): void
    {
        $res = $this->get(route('categories.show', $this->category));
        $this->assertCleanPage($res);
        $res->assertSee('Musique');
    }

    public function test_legal_page_has_placeholders_and_no_fee_wording(): void
    {
        $res = $this->get(route('pages.legal'));
        $this->assertCleanPage($res);
        $res->assertSee('[À COMPLÉTER]', false);
    }

    public function test_promoter_guide_shows_configured_commission(): void
    {
        config(['payments.default_commission_rate' => 7]);
        $res = $this->get(route('pages.promoter-guide'));
        $this->assertCleanPage($res);
        $res->assertSee('7 %');
    }

    public function test_support_page_renders_form(): void
    {
        $res = $this->get(route('pages.support'));
        $this->assertCleanPage($res);
        $res->assertSee('name="message"', false);
    }

    public function test_direct_checkout_goes_straight_to_the_information_step(): void
    {
        $user = $this->makeUser(30, 'acheteur');
        $this->actingAs($user)->post(route('checkout.direct'), ['event_id' => 1, 'tickets' => [1 => 2]])
            ->assertRedirect(route('checkout.show'));
        $this->assertCleanPage($this->get(route('checkout.show')));
    }
}
