<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewa_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('ewa_bill_id')->constrained('ewa_bills')->cascadeOnDelete();
            $table->decimal('amount', 10, 3);
            $table->date('payment_date');
            $table->string('method', 30); // cash|bank_transfer|cheque|online_card
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('ewa_bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewa_payments');
    }
};
