<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `deleted_at` on the records a user can destroy, plus the index work that
 * makes soft deletes actually usable.
 *
 * Today every delete in this app is permanent: only Admin can do it, and all
 * that survives is an audit-log line saying it happened. This is the schema
 * half of making that recoverable.
 *
 * ── WHY THE INDEXES ARE REBUILT ──────────────────────────────────────────────
 * Every one of these tables has a unique business code — property_code,
 * tenant_code, invoice_number, job_order, and so on. With a plain unique index,
 * a trashed row keeps its code, so re-creating the record it stood for fails on
 * a collision with a row the user cannot see. The error would be unexplainable
 * from the UI.
 *
 * A composite UNIQUE(code, deleted_at) does not fix it either: SQL treats NULLs
 * as distinct in a unique index, so two LIVE rows (both NULL) would be allowed
 * — turning the constraint off exactly where it matters.
 *
 * The correct tool is a partial index: uniqueness enforced only over live rows.
 * SQLite (3.8+, this box runs 3.40) and PostgreSQL both support it. MySQL does
 * not, so on MySQL the indexes are left alone and the guard below says so
 * rather than silently doing nothing.
 *
 * ── WHAT THIS MIGRATION DOES NOT DO ──────────────────────────────────────────
 * It does not add the SoftDeletes trait to any model, so nothing changes
 * behaviour: every existing row has deleted_at NULL, a partial index over live
 * rows is identical to the plain index it replaced, and deletes stay hard.
 *
 * That is on purpose. Turning the traits on is a behaviour change that has to
 * deal with two ON DELETE CASCADE relationships which stop firing the moment a
 * delete becomes an UPDATE:
 *
 *     floors.building_id   → buildings  ON DELETE CASCADE
 *     payments.invoice_id  → invoices   ON DELETE CASCADE
 *
 * Soft-deleting a building would leave its floors live and parentless, and
 * soft-deleting an invoice would leave its payments pointing at a row nothing
 * displays. Cascading those by hand, and providing a way to restore, is its own
 * step with its own tests.
 */
return new class extends Migration
{
    /** Tables that get a deleted_at column. */
    private const TABLES = [
        'buildings', 'floors', 'property_units', 'tenants', 'lease_contracts',
        'invoices', 'payments', 'ewa_bills', 'ewa_payments', 'expenses',
        'revenues', 'maintenance_requests', 'users',
    ];

    /** index name => [table, [columns…]] — rebuilt to cover live rows only. */
    private const UNIQUE_INDEXES = [
        'users_name_unique'                            => ['users', ['name']],
        'users_email_unique'                           => ['users', ['email']],
        'buildings_property_code_unique'               => ['buildings', ['property_code']],
        'floors_building_id_floor_name_unique'         => ['floors', ['building_id', 'floor_name']],
        'tenants_tenant_code_unique'                   => ['tenants', ['tenant_code']],
        'lease_contracts_lease_agreement_no_unique'    => ['lease_contracts', ['lease_agreement_no']],
        'invoices_invoice_number_unique'               => ['invoices', ['invoice_number']],
        'payments_payment_number_unique'               => ['payments', ['payment_number']],
        'ewa_bills_bill_number_unique'                 => ['ewa_bills', ['bill_number']],
        'ewa_payments_payment_number_unique'           => ['ewa_payments', ['payment_number']],
        'maintenance_requests_job_order_unique'        => ['maintenance_requests', ['job_order']],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
                // Every read filters on it, so it earns an index.
                $t->index('deleted_at');
            });
        }

        $this->rebuildUniqueIndexes(partial: true);
    }

    public function down(): void
    {
        $this->rebuildUniqueIndexes(partial: false);

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // The index has to go before the column it covers.
                $t->dropIndex($table.'_deleted_at_index');
                $t->dropSoftDeletes();
            });
        }
    }

    private function rebuildUniqueIndexes(bool $partial): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['sqlite', 'pgsql'], true)) {
            // Say it out loud: on MySQL a trashed row would still hold its
            // code, and the traits must not be enabled until that is solved
            // (a generated column, or uniqueness enforced in the application).
            echo "  [skipped] {$driver} has no partial indexes — unique codes are NOT soft-delete aware.\n";

            return;
        }

        foreach (self::UNIQUE_INDEXES as $name => [$table, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $quoted = implode(', ', array_map(fn ($c) => '"'.$c.'"', $columns));

            DB::statement('DROP INDEX IF EXISTS "'.$name.'"');
            DB::statement(sprintf(
                'CREATE UNIQUE INDEX "%s" ON "%s" (%s)%s',
                $name,
                $table,
                $quoted,
                $partial ? ' WHERE "deleted_at" IS NULL' : ''
            ));
        }
    }
};
