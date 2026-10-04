<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Export history — what was generated, by whom, over what.
 *
 * The design handoff's Reports screen carries a "Recent exports" panel
 * (filename, who ran it, when, download). There was nothing to build it from:
 * every export in this app streams a file and leaves no trace, so there is no
 * way to answer "who pulled the VAT return in July".
 *
 * Deliberately records the REQUEST, not the file: `filters` is the parameter
 * set the export ran with, so a row can be re-run rather than re-downloaded.
 * Keeping generated spreadsheets on disk would mean a retention policy, a
 * storage budget and a privacy question about tenant data sitting in a folder;
 * re-running from the parameters avoids all three.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete: removing a user must not erase the
            // record that an export happened.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 255)->nullable();   // survives the user row
            $table->string('kind', 60);                     // 'reports.vat-return', 'export.units', …
            $table->string('label', 160);                   // 'VAT Return'
            $table->string('format', 10);                   // pdf | xlsx | csv
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['kind', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
