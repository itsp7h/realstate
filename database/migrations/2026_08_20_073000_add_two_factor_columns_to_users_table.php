<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for two-factor authentication.
 *
 * Columns only — nothing reads them yet. Landed ahead of the feature so the
 * schema change and the behaviour change are separate, reversible steps: this
 * one cannot affect a signed-in user, because no code path touches it.
 *
 * The secret and the recovery codes are stored encrypted by the application
 * (Crypt cast), which is why both are text rather than fixed-width.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            // Null until the user has proved they can produce a valid code, so
            // a half-finished enrolment never locks anyone out.
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
