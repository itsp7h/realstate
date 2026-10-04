<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The contact email printed on the PDF letterhead, beside the address, CR
 * number and phone.
 *
 * A column rather than a constant in the Blade template because the rest of
 * that line is about to follow it: the address, CR and phone are still
 * hardcoded, and this is the first of them to become editable. Nullable and
 * with no default, so an unset value simply drops off the letterhead line
 * instead of printing a placeholder onto a tenant's invoice.
 *
 * Additive, not a change to the create migration: that one is already committed
 * and has run on staging, so editing it in place would need a rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_settings', function (Blueprint $table) {
            // 255 to match the Form Request's max: and the app's other email
            // columns; after secondary_color, being the newest field.
            $table->string('company_email', 255)->nullable()->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table) {
            $table->dropColumn('company_email');
        });
    }
};
