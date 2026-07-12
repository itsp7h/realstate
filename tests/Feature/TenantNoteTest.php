<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantNoteTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ], $overrides));
    }

    private function makeInvoice(Tenant $tenant, array $overrides = []): Invoice
    {
        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => 500.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2024-03-01',
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    public function test_can_issue_credit_note_against_tenant_with_no_invoice(): void
    {
        $tenant = $this->makeTenant();

        $this->post(route('tenants.notes.store', $tenant), [
            'type'      => 'credit',
            'amount'    => '75.000',
            'note_date' => '2024-03-10',
            'reason'    => 'Goodwill gesture',
        ])->assertRedirect(route('tenants.show', ['tenant' => $tenant, 'tab' => 'notes']));

        $this->assertDatabaseHas('invoice_notes', [
            'tenant_id'  => $tenant->id,
            'invoice_id' => null,
            'type'       => 'credit',
            'amount'     => 75.000,
        ]);
    }

    public function test_can_issue_debit_note_against_tenant(): void
    {
        $tenant = $this->makeTenant();

        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'debit', 'amount' => '40.000', 'note_date' => '2024-03-10', 'reason' => 'Undercharge correction',
        ]);

        $this->assertDatabaseHas('invoice_notes', ['tenant_id' => $tenant->id, 'invoice_id' => null, 'type' => 'debit', 'amount' => 40.000]);
    }

    public function test_note_number_uses_correct_prefix(): void
    {
        $tenant = $this->makeTenant();

        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '10.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);

        $this->assertStringStartsWith('CN-', InvoiceNote::first()->note_number);
    }

    public function test_tenant_level_note_does_not_affect_any_invoice_balance(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);

        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '200.000', 'note_date' => '2024-03-10', 'reason' => 'Account credit',
        ]);

        $invoice->refresh();
        $this->assertEquals(500.000, $invoice->balance_due);
    }

    public function test_deleting_tenant_level_note_removes_it(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);
        $note = InvoiceNote::first();

        $this->delete(route('tenants.notes.destroy', [$tenant, $note]))
            ->assertRedirect(route('tenants.show', ['tenant' => $tenant, 'tab' => 'notes']));

        $this->assertDatabaseMissing('invoice_notes', ['id' => $note->id]);
    }

    // ── VALIDATION ────────────────────────────────────────────────

    public function test_store_fails_with_invalid_type(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'invalid', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ])->assertSessionHasErrors('type');
    }

    public function test_store_fails_without_reason(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10',
        ])->assertSessionHasErrors('reason');
    }

    // ── TENANT PROFILE + LEDGER INTEGRATION ─────────────────────────

    public function test_tenant_profile_shows_general_note_as_such(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Goodwill',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertSee('General adjustment')
            ->assertSee('Goodwill');
    }

    public function test_credit_note_reduces_tenant_statement_running_balance(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, ['invoice_date' => now()->subDays(5)->format('Y-m-d')]);

        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'credit', 'amount' => '100.000', 'note_date' => now()->subDays(3)->format('Y-m-d'), 'reason' => 'Adjustment',
        ]);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);

        // Invoice balance (500) minus the general credit note (100) = 400.
        $this->assertEquals(400.000, $response->viewData('total'));
    }

    public function test_debit_note_increases_group_ageing_pending(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('tenants.notes.store', $tenant), [
            'type' => 'debit', 'amount' => '60.000', 'note_date' => now()->subDays(1)->format('Y-m-d'), 'reason' => 'Extra charge',
        ]);

        $response = $this->get(route('reports.group-ageing'));
        $groups   = $response->viewData('groups');

        $row = $groups->firstWhere(fn ($g) => $g['tenant']->id === $tenant->id);
        $this->assertNotNull($row);
        $this->assertEquals(60.000, $row['pending']);
    }
}
