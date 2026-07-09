<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewa_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 30)->unique();
            $table->foreignId('lease_contract_id')->nullable()->constrained('lease_contracts')->nullOnDelete();
            $table->string('tenant_name', 255);
            $table->string('property_name', 255)->nullable();
            $table->string('unit', 100)->nullable();

            // EWA account & period
            $table->string('ewa_account_number', 50)->nullable();
            $table->string('billing_period', 30);        // e.g. "April 2024"
            $table->date('reading_date')->nullable();
            $table->string('reading_type', 10)->default('actual'); // actual | estimated

            // Electricity
            $table->decimal('elec_prev_reading', 10, 0)->nullable();
            $table->decimal('elec_curr_reading', 10, 0)->nullable();
            $table->decimal('elec_consumption', 10, 0)->nullable(); // kWh
            $table->decimal('elec_charges', 10, 3)->nullable();

            // Water
            $table->decimal('water_prev_reading', 10, 3)->nullable();
            $table->decimal('water_curr_reading', 10, 3)->nullable();
            $table->decimal('water_consumption', 10, 3)->nullable(); // m³
            $table->decimal('water_charges', 10, 3)->nullable();

            // Fees & subsidy
            $table->decimal('municipality_fee', 10, 3)->default(0);
            $table->decimal('subsidy', 10, 3)->default(0);

            // Totals
            $table->decimal('total_amount', 10, 3);
            $table->date('due_date');
            $table->string('status', 20)->default('issued');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('lease_contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewa_bills');
    }
};
