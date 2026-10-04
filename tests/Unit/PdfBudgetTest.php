<?php

namespace Tests\Unit;

use App\Support\PdfBudget;
use App\Support\PdfPageNumbers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The budget that lets a long report PDF render at all.
 *
 * The decision logic is tested as a pure function — targetMemoryLimit() — so
 * these assertions do not depend on whatever limit the test runner happens to
 * be using. One test does exercise the real wiring, because the bug this fixes
 * was not in the arithmetic: it was that nothing raised the limit before
 * DomPDF started laying out 1,048 rows.
 */
class PdfBudgetTest extends TestCase
{
    // The stamp() test needs the container (the Pdf facade and pdf.css), and
    // the base TestCase signs in a factory user, so the schema has to exist.
    use RefreshDatabase;

    private string|false $originalLimit = false;

    protected function tearDown(): void
    {
        if ($this->originalLimit !== false) {
            ini_set('memory_limit', $this->originalLimit);
        }

        parent::tearDown();
    }

    public function test_it_parses_the_shapes_php_ini_uses(): void
    {
        $this->assertSame(134217728, PdfBudget::toBytes('128M'));
        $this->assertSame(134217728, PdfBudget::toBytes('134217728'));
        $this->assertSame(1073741824, PdfBudget::toBytes('1G'));
        $this->assertSame(131072, PdfBudget::toBytes('128K'));
        $this->assertSame(-1, PdfBudget::toBytes('-1'));
        $this->assertSame(134217728, PdfBudget::toBytes(' 128m '));
    }

    public function test_it_does_not_guess_at_values_it_cannot_read(): void
    {
        $this->assertNull(PdfBudget::toBytes(''));
        $this->assertNull(PdfBudget::toBytes('lots'));
        $this->assertNull(PdfBudget::toBytes('128MB'));
    }

    public function test_it_raises_a_limit_that_is_too_low_for_a_long_report(): void
    {
        // php-fpm's shipped value, and the one both broken reports died on.
        $this->assertSame(PdfBudget::MEMORY, PdfBudget::targetMemoryLimit('128M'));
        $this->assertSame(PdfBudget::MEMORY, PdfBudget::targetMemoryLimit('256M'));
    }

    public function test_it_never_lowers_a_limit_it_finds(): void
    {
        // A deployment that has been given more keeps it: this is a floor for
        // one render, not a policy for the process.
        $this->assertNull(PdfBudget::targetMemoryLimit('1G'));
        $this->assertNull(PdfBudget::targetMemoryLimit('-1'), 'unlimited must be left alone');
        $this->assertNull(PdfBudget::targetMemoryLimit(PdfBudget::MEMORY));
    }

    public function test_it_leaves_an_unreadable_limit_untouched(): void
    {
        $this->assertNull(PdfBudget::targetMemoryLimit('whatever'));
    }

    public function test_reserving_raises_the_running_limit(): void
    {
        $this->originalLimit = ini_get('memory_limit');

        ini_set('memory_limit', '128M');

        PdfBudget::reserve();

        $this->assertSame(
            PdfBudget::toBytes(PdfBudget::MEMORY),
            PdfBudget::toBytes((string) ini_get('memory_limit')),
        );
    }

    /**
     * The wiring, not the arithmetic. Every PDF the app streams goes through
     * PdfPageNumbers::stamp(), which is where the render happens — so if the
     * reserve call is ever removed from it, the long reports go back to
     * returning 500s that no CLI test would notice.
     */
    public function test_stamping_a_pdf_reserves_the_budget_before_rendering(): void
    {
        $this->originalLimit = ini_get('memory_limit');

        ini_set('memory_limit', '128M');

        // A trivial document: what is under test is that stamp() raised the
        // ceiling, not that this page needed it.
        PdfPageNumbers::stamp(Pdf::loadHTML('<p>one page</p>')->setPaper('a4', 'portrait'));

        $this->assertSame(
            PdfBudget::toBytes(PdfBudget::MEMORY),
            PdfBudget::toBytes((string) ini_get('memory_limit')),
            'stamp() no longer reserves a budget, so a long report PDF will exhaust php-fpm memory',
        );
    }

    public function test_the_time_limit_stays_below_nginxs_own_timeout(): void
    {
        // nginx's fastcgi_read_timeout is not set in realstate.conf, so it is
        // 60s. PHP has to give up first, or the user gets a 504 with nothing
        // in the PHP log to explain it.
        $this->assertLessThan(60, PdfBudget::SECONDS);
    }
}
