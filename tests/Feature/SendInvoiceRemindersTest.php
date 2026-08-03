<?php

namespace Tests\Feature;

use App\Mail\InvoiceOverdueReminderMail;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendInvoiceRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = Tenant::create([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
            'email'       => array_key_exists('tenant_email', $overrides) ? $overrides['tenant_email'] : 'tenant@example.com',
        ]);
        unset($overrides['tenant_email']);

        $amount = $overrides['amount'] ?? 200.000;
        unset($overrides['amount']);

        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'tenant_code'    => $tenant->tenant_code,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => $amount]],
            'vat_rate'       => 0,
            'invoice_date'   => now()->subDays(10)->toDateString(),
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    public function test_marks_past_due_unpaid_invoice_as_overdue_and_emails_tenant(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice();

        $this->artisan('invoices:send-overdue-reminders')->assertExitCode(0);

        $this->assertSame('overdue', $invoice->fresh()->status);
        Mail::assertSent(InvoiceOverdueReminderMail::class, function (InvoiceOverdueReminderMail $mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id && $mail->daysOverdue === 10;
        });
    }

    public function test_does_not_touch_invoices_not_yet_due(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(['invoice_date' => now()->addDays(5)->toDateString()]);

        $this->artisan('invoices:send-overdue-reminders');

        $this->assertSame('issued', $invoice->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_does_not_touch_fully_paid_invoices(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(['amount' => 100.000]);
        $invoice->payments()->create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'amount'         => 100.000,
            'payment_date'   => now()->toDateString(),
            'method'         => 'cash',
        ]);
        $invoice->syncStatus();

        $this->artisan('invoices:send-overdue-reminders');

        $this->assertSame('paid', $invoice->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_does_not_email_tenant_without_email_but_still_marks_overdue(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(['tenant_email' => null]);

        $this->artisan('invoices:send-overdue-reminders');

        $this->assertSame('overdue', $invoice->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_does_not_touch_cancelled_invoices(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(['status' => 'cancelled']);

        $this->artisan('invoices:send-overdue-reminders');

        $this->assertSame('cancelled', $invoice->fresh()->status);
        Mail::assertNothingSent();
    }
}
