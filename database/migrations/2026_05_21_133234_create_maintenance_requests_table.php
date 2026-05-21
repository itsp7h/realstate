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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('job_order', 100)->unique()->nullable();
            $table->string('property', 255);
            $table->string('tenant', 255);
            $table->string('flat', 100);
            $table->string('contact_no', 50);
            $table->dateTime('available_datetime');
            $table->enum('apartment_status', ['occupied', 'vacant', 'furnished', 'other']);
            $table->date('request_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');

            // Supervisor
            $table->string('supervisor_name', 255)->nullable();
            $table->dateTime('supervisor_datetime')->nullable();

            // Maintenance use only
            $table->text('job_assessment')->nullable();
            $table->decimal('quotation_1', 10, 3)->nullable();
            $table->decimal('quotation_2', 10, 3)->nullable();
            $table->decimal('quotation_3', 10, 3)->nullable();
            $table->text('maintenance_remarks')->nullable();

            // Approval
            $table->string('approved_supervisor', 255)->nullable();
            $table->string('approved_dept_head', 255)->nullable();

            // Job lines stored as JSON
            $table->json('job_lines')->nullable();

            $table->timestamps();

            $table->index(['status', 'date']);
            $table->index('property');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
