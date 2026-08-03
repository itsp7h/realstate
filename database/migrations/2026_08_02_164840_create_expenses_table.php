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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('property_units')->nullOnDelete();
            $table->string('category', 50);
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 12, 3);
            $table->date('expense_date');
            $table->string('vendor_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['building_id', 'expense_date']);
            $table->index(['unit_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
