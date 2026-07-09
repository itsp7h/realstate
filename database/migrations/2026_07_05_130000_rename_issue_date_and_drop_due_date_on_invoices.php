<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'issue_date') && ! Schema::hasColumn('invoices', 'invoice_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('issue_date', 'invoice_date');
            });
        }

        if (Schema::hasColumn('invoices', 'due_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('invoices_due_date_index');
            });
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('due_date')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('invoice_date', 'issue_date');
        });
    }
};
