<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN — rebuild the table with updated CHECK constraint
        DB::statement('CREATE TABLE invoices_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number VARCHAR(30) NOT NULL UNIQUE,
            lease_contract_id INTEGER NOT NULL REFERENCES lease_contracts(id) ON DELETE CASCADE,
            tenant_name VARCHAR(255) NOT NULL,
            property_name VARCHAR(255),
            unit VARCHAR(100),
            type VARCHAR(255) NOT NULL CHECK(type IN (\'rent\',\'utilities\',\'ewa\',\'other\')),
            description VARCHAR(500),
            amount NUMERIC(10,3) NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT \'draft\'
                CHECK(status IN (\'draft\',\'issued\',\'partially_paid\',\'paid\',\'overdue\',\'cancelled\')),
            notes TEXT,
            created_at DATETIME,
            updated_at DATETIME
        )');

        DB::statement('INSERT INTO invoices_new SELECT * FROM invoices');
        DB::statement('DROP TABLE invoices');
        DB::statement('ALTER TABLE invoices_new RENAME TO invoices');

        DB::statement('CREATE INDEX invoices_lease_contract_id_status_index ON invoices (lease_contract_id, status)');
        DB::statement('CREATE INDEX invoices_due_date_index ON invoices (due_date)');
    }

    public function down(): void
    {
        DB::statement('CREATE TABLE invoices_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number VARCHAR(30) NOT NULL UNIQUE,
            lease_contract_id INTEGER NOT NULL REFERENCES lease_contracts(id) ON DELETE CASCADE,
            tenant_name VARCHAR(255) NOT NULL,
            property_name VARCHAR(255),
            unit VARCHAR(100),
            type VARCHAR(255) NOT NULL CHECK(type IN (\'rent\',\'utilities\',\'other\')),
            description VARCHAR(500),
            amount NUMERIC(10,3) NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT \'draft\'
                CHECK(status IN (\'draft\',\'issued\',\'partially_paid\',\'paid\',\'overdue\',\'cancelled\')),
            notes TEXT,
            created_at DATETIME,
            updated_at DATETIME
        )');

        DB::statement('INSERT INTO invoices_new SELECT * FROM invoices WHERE type != \'ewa\'');
        DB::statement('DROP TABLE invoices');
        DB::statement('ALTER TABLE invoices_new RENAME TO invoices');

        DB::statement('CREATE INDEX invoices_lease_contract_id_status_index ON invoices (lease_contract_id, status)');
        DB::statement('CREATE INDEX invoices_due_date_index ON invoices (due_date)');
    }
};
