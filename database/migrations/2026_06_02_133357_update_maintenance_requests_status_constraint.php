<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE "maintenance_requests_new" (
                "id" integer primary key autoincrement not null,
                "date" date not null,
                "job_order" varchar,
                "property" varchar not null,
                "tenant" varchar not null,
                "flat" varchar not null,
                "contact_no" varchar not null,
                "available_datetime" datetime not null,
                "apartment_status" varchar check ("apartment_status" in (\'occupied\', \'vacant\', \'furnished\', \'other\')) not null,
                "request_date" date,
                "status" varchar check ("status" in (\'waiting_supervisor\', \'waiting_approval\', \'approved\', \'in_progress\', \'completed\', \'cancelled\')) not null default \'waiting_supervisor\',
                "supervisor_name" varchar,
                "supervisor_datetime" datetime,
                "job_assessment" text,
                "quotation_1" numeric,
                "quotation_1_file" varchar,
                "quotation_2" numeric,
                "quotation_2_file" varchar,
                "quotation_3" numeric,
                "quotation_3_file" varchar,
                "selected_quotation" integer,
                "maintenance_remarks" text,
                "approved_supervisor" varchar,
                "approved_dept_head" varchar,
                "job_lines" text,
                "created_at" datetime,
                "updated_at" datetime
            )
        ');

        DB::statement('
            INSERT INTO "maintenance_requests_new"
            SELECT
                id, date, job_order, property, tenant, flat, contact_no,
                available_datetime, apartment_status, request_date,
                CASE status WHEN \'open\' THEN \'waiting_supervisor\' ELSE status END,
                supervisor_name, supervisor_datetime, job_assessment,
                quotation_1, quotation_1_file, quotation_2, quotation_2_file,
                quotation_3, quotation_3_file, selected_quotation,
                maintenance_remarks, approved_supervisor, approved_dept_head,
                job_lines, created_at, updated_at
            FROM "maintenance_requests"
        ');

        DB::statement('DROP TABLE "maintenance_requests"');
        DB::statement('ALTER TABLE "maintenance_requests_new" RENAME TO "maintenance_requests"');

        DB::statement('CREATE UNIQUE INDEX "maintenance_requests_job_order_unique" ON "maintenance_requests" ("job_order")');
        DB::statement('CREATE INDEX "maintenance_requests_status_date_index" ON "maintenance_requests" ("status", "date")');
        DB::statement('CREATE INDEX "maintenance_requests_property_index" ON "maintenance_requests" ("property")');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Reversing to old statuses would lose data — not safely reversible
    }
};
