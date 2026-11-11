<?php

namespace Tests\Feature;

use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * I1 (relecteur frais de service) : le total AFFICHE (checkout connecte, reservation directe, panier) vaut la somme
 * des lignes, jamais sous-total + 500, et aucune ligne « frais » n'apparait. Les montants de test sont choisis pour
 * qu'aucune valeur erronee (somme + 500) ne puisse apparaitre par hasard dans la page.
 */
class DisplayedTotalNoFeeTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 3, 'title' => 'Festival', 'venue' => 'Parc', 'event_date' => '2027-02-01', 'event_time' => '18:00']);
    }

    private function line(int $eventId, int $price, int $qty, string $name): array
    {
        $type = TicketType::create([
            'event_id' => $eventId, 'name' => $name, 'price' => $price, 'quantity_available' => 100, 'quantity_sold' => 0,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(), 'max_per_order' => 10, 'is_active' => true,
        ]);

        return [
            'ticket_type_id' => $type->id, 'event_id' => $eventId, 'quantity' => $qty, 'ticket_name' => $name,
            'unit_price' => $price, 'total_price' => $price * $qty, 'event_title' => 'Concert', 'event_image' => null,
            'event_date' => '2027-01-01', 'event_time' => '20:00', 'event_venue' => 'Palais', 'event_slug' => 'concert',
        ];
    }

    /** @return array<string, array{0: array, 1: int}> panier => [lignes, total attendu] */
    public static function cartShapes(): array
    {
        return [
            'une ligne' => [[[1, 4000, 2, 'Std']], 8000],
            'plusieurs lignes et evenements' => [[[1, 3333, 2, 'Std'], [1, 5000, 1, 'VIP'], [2, 2500, 3, 'Early']], 19166],
        ];
    }

    private function build(array $specs): array
    {
        $cart = [];
        foreach ($specs as $i => [$event, $price, $qty, $name]) {
            $cart['k' . $i] = $this->line($event, $price, $qty, $name);
        }

        return $cart;
    }

    private function assertTotalIs(\Illuminate\Testing\TestResponse $res, int $total): void
    {
        $res->assertOk()
            ->assertSee(\App\Helpers\CurrencyHelper::formatFCFA($total), false)
            ->assertDontSee(\App\Helpers\CurrencyHelper::formatFCFA($total + 500), false)
            ->assertDontSee('Frais de service')->assertDontSee('Frais techniques')->assertDontSee('frais de service');
    }

    #[DataProvider('cartShapes')]
    public function test_authenticated_checkout_displays_exact_total(array $specs, int $total): void
    {
        $res = $this->actingAs(User::find(1))->withSession(['cart' => $this->build($specs)])->get(route('checkout.show'));
        $this->assertTotalIs($res, $total);
    }

    // La reservation directe affiche aussi checkout.show (session direct_booking ; la route `direct` est POST et redirige).
    #[DataProvider('cartShapes')]
    public function test_direct_booking_checkout_displays_exact_total(array $specs, int $total): void
    {
        $res = $this->actingAs(User::find(1))->withSession(['direct_booking' => $this->build($specs)])->get(route('checkout.show'));
        $this->assertTotalIs($res, $total);
    }

    #[DataProvider('cartShapes')]
    public function test_cart_page_displays_exact_total(array $specs, int $total): void
    {
        $res = $this->withSession(['cart' => $this->build($specs)])->get(route('cart.show'));
        $this->assertTotalIs($res, $total);
        // Valeur initiale rendue pour le JS mobile (id mobileTotal) = somme des lignes
        $res->assertSee('id="mobileTotal">' . \App\Helpers\CurrencyHelper::formatFCFA($total), false);
    }

    public function test_client_js_and_views_add_no_fixed_amount_to_totals(): void
    {
        $files = [];
        foreach (['checkout', 'cart', 'emails'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/' . $dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                    $files[] = $f->getPathname();
                }
            }
        }
        foreach (glob(public_path('js/{cart,checkout}*.js'), GLOB_BRACE) as $js) {
            $files[] = $js;
        }
        $this->assertNotEmpty($files);

        $offenders = [];
        foreach ($files as $file) {
            // Les fichiers .old ne sont pas servis ; le panier est verifie en entier car il contient le JS inline.
            if (str_contains($file, '.old')) {
                continue;
            }
            if (preg_match('/\+\s*500\b|\b500\s*\+|service_?fee|serviceFee|platform_?fee/i', (string) file_get_contents($file), $m)) {
                $offenders[] = basename($file) . ' : ' . $m[0];
            }
        }

        $this->assertSame([], $offenders, 'Aucun total ne doit ajouter une constante 500 / des frais de service');
    }
}
