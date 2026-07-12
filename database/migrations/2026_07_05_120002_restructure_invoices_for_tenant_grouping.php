<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The invoicing module has not shipped yet (no real invoices exist), so the
        // table is recreated outright to move from one-invoice-per-lease to
        // one-invoice-per-tenant with multiple rental lines, instead of altering
        // columns (SQLite's ALTER COLUMN support requires doctrine/dbal, which
        // this project does not install).
        Schema::dropIfExists('invoices');

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('tenant_name', 255);
            $table->string('tenant_code', 30)->nullable();
            $table->string('tenant_address', 500)->nullable();
            $table->string('property_name', 255)->nullable();
            $table->string('unit', 100)->nullable();
            $table->enum('type', ['rent', 'utilities', 'other']);
            $table->string('description', 500)->nullable();
            $table->json('lines')->nullable();
            $table->decimal('amount', 10, 3);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 10, 3)->default(0);
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
