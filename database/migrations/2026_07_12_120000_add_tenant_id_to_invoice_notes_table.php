<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_notes_new', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 30)->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 3);
            $table->date('note_date');
            $table->string('reason', 500);
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
            $table->index(['tenant_id', 'type']);
        });

        DB::statement('
            INSERT INTO invoice_notes_new (id, note_number, invoice_id, tenant_id, type, amount, note_date, reason, created_at, updated_at)
            SELECT n.id, n.note_number, n.invoice_id, i.tenant_id, n.type, n.amount, n.note_date, n.reason, n.created_at, n.updated_at
            FROM invoice_notes n
            LEFT JOIN invoices i ON i.id = n.invoice_id
        ');

        Schema::drop('invoice_notes');
        Schema::rename('invoice_notes_new', 'invoice_notes');
    }

    public function down(): void
    {
        Schema::create('invoice_notes_old', function (Blueprint $table) {
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

        DB::statement('
            INSERT INTO invoice_notes_old (id, note_number, invoice_id, type, amount, note_date, reason, created_at, updated_at)
            SELECT id, note_number, invoice_id, type, amount, note_date, reason, created_at, updated_at
            FROM invoice_notes
            WHERE invoice_id IS NOT NULL
        ');

        Schema::drop('invoice_notes');
        Schema::rename('invoice_notes_old', 'invoice_notes');
    }
};
