<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Les codes de billet sont saisis à la main quand un QR est illisible : aucun caractère ambigu (0/O, 1/I/L),
 * et le format reste celui qu'attend le scanner (« TKT- » + 8 caractères A-Z / 0-9).
 */
class TicketCodeFormatTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
    }

    public function test_generated_codes_have_no_ambiguous_characters_and_match_the_scanner_format(): void
    {
        $seen = [];
        for ($i = 0; $i < 400; $i++) {
            $code = Ticket::generateTicketCode();

            $this->assertMatchesRegularExpression('/^TKT-[A-Z0-9]{8}$/', $code, 'format attendu par le scanner');
            $this->assertMatchesRegularExpression('/^TKT-[2-9A-HJKMNP-Z]{8}$/', $code, 'jamais 0, 1, I, L ni O');
            $seen[$code] = true;
        }

        $this->assertGreaterThan(395, count($seen), 'les codes doivent être très majoritairement distincts');
    }

    public function test_alphabet_has_no_ambiguous_characters(): void
    {
        foreach (['0', 'O', '1', 'I', 'L'] as $bad) {
            $this->assertStringNotContainsString($bad, Ticket::CODE_ALPHABET);
        }
        $this->assertSame(31, strlen(Ticket::CODE_ALPHABET));
        $this->assertSame(31, count(array_unique(str_split(Ticket::CODE_ALPHABET))));
    }
}
