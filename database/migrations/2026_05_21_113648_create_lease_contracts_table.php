<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lease_contracts', function (Blueprint $table) {
            $table->id();

            // Contract identity
            $table->date('date');
            $table->string('lease_agreement_no', 100)->unique()->index();

            // Tenant (FK + denormalized name for import convenience)
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_name', 255);

            // Property location (denormalized from source file)
            $table->string('property_name', 255)->nullable();
            $table->string('property_code', 50)->nullable()->index();
            $table->string('block_name', 100)->nullable();
            $table->string('block_code', 50)->nullable();
            $table->string('floor_name', 100)->nullable();
            $table->string('floor_code', 50)->nullable();

            // Unit (FK + denormalized identifier)
            $table->foreignId('unit_id')->nullable()->constrained('property_units')->nullOnDelete();
            $table->string('unit', 100)->nullable();

            // Fit-out / description
            $table->string('description', 100)->nullable();  // Fitted, Shell & Core, Semi-Fitted

            // Lease term
            $table->date('lease_start_date');
            $table->date('lease_end_date');
            $table->date('lease_break_date')->nullable();
            $table->string('notice_period', 50)->nullable();  // e.g. "1 Month"

            // Accounting
            $table->string('rental_income_ledger', 50)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('security_deposit', 12, 3)->nullable();

            // Rent component
            $table->string('invoicing_frequency', 50)->nullable();
            $table->date('rent_start_date')->nullable();
            $table->date('rent_end_date')->nullable();
            $table->decimal('rent_per_month', 12, 3)->nullable();

            // Service charge component
            $table->string('service_frequency', 50)->nullable();
            $table->date('service_start_date')->nullable();
            $table->date('service_end_date')->nullable();
            $table->decimal('service_amount_bd_excl_vat', 12, 3)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_contracts');
    }
};
