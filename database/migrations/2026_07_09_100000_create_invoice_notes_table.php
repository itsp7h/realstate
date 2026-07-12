<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 30)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 3);
            $table->date('note_date');
            $table->string('reason', 500);
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_notes');
    }
};
