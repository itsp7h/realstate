<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Columns were renamed before the create migration was finalized.
        // The create migration already uses the correct final names, so this is a no-op.
    }

    public function down(): void
    {
        // Nothing to reverse.
    }
};
