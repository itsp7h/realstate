# Real Estate Schema — ERD

Reconstructed from all 49 migrations (reflects final state after every create/alter/rename/drop). Auth/queue/cache infra tables omitted — no business relationships.

> Open note: `unit_type` (`Office`/`Shop`/etc.) lives as a plain string on `property_units`, and `floors.block_name` is a free-text label, not a real entity. Pending confirmation with the real estate department on whether these need first-class modeling.

```mermaid
erDiagram
    BUILDINGS ||--o{ FLOORS : "has"
    BUILDINGS ||--o{ PROPERTY_UNITS : "has"
    BUILDINGS ||--o{ BUILDING_IMAGES : "has"
    BUILDINGS ||--o{ MAINTENANCE_REQUESTS : "location for"
    BUILDINGS ||--o{ EXPENSES : "incurs"
    BUILDINGS ||--o{ REVENUES : "earns"
    FLOORS ||--o{ PROPERTY_UNITS : "contains"
    PROPERTY_UNITS ||--o{ LEASE_CONTRACTS : "leased via"
    PROPERTY_UNITS ||--o{ MAINTENANCE_REQUESTS : "subject of"
    PROPERTY_UNITS ||--o{ EXPENSES : "incurs"
    PROPERTY_UNITS ||--o{ REVENUES : "earns"
    TENANTS ||--o{ LEASE_CONTRACTS : "signs"
    TENANTS ||--o{ INVOICES : "billed"
    TENANTS ||--o{ INVOICE_NOTES : "credited/debited"
    LEASE_CONTRACTS ||--o{ EWA_BILLS : "utilities under"
    INVOICES ||--o{ PAYMENTS : "settled by"
    INVOICES ||--o{ INVOICE_NOTES : "adjusted by"
    EWA_BILLS ||--o{ EWA_PAYMENTS : "settled by"
    EWA_BILLS ||--o{ PAYMENTS : "also settled by"
    USERS ||--o{ EXPENSES : "records"
    USERS ||--o{ REVENUES : "records"

    BUILDINGS {
        int id PK
        string property_name
        string property_code UK
        string property_type
        string type_of_ownership
        string land_lord_name
        int total_no_of_blocks
        int total_no_of_floors
        int total_no_of_units
        json custom_fields
        bool vat_enabled
        decimal vat_rate
    }

    FLOORS {
        int id PK
        int building_id FK
        string floor_name
        string floor_code
        string block_name "free-text label, not FK"
        string block_code
        int total_no_of_units
    }

    PROPERTY_UNITS {
        int id PK
        int building_id FK
        int floor_id FK
        string unit_name
        string unit_type "Studio/1BHK.../Office/Commercial"
        string unit_condition
        string property_code
        decimal area_inside
        decimal rent_per_month
        decimal security_deposit_amount
        json custom_fields
    }

    BUILDING_IMAGES {
        int id PK
        int building_id FK
        string path
        int sort_order
    }

    TENANTS {
        int id PK
        string tenant_code UK
        string name
        enum tenant_type "individual/company"
        string company_name
        string id_cr_number
        string phone
        string email
        string address
    }

    LEASE_CONTRACTS {
        int id PK
        int tenant_id FK
        int unit_id FK
        string lease_agreement_no UK
        date lease_start_date
        date lease_end_date
        date lease_break_date
        decimal rent_per_month
        decimal security_deposit
        decimal ewa_cap
        bool vat_enabled
        decimal vat_rate
        string invoicing_frequency
        string service_frequency
    }

    INVOICES {
        int id PK
        int tenant_id FK
        string invoice_number UK
        enum type "rent/utilities/other"
        json lines
        decimal amount
        decimal vat_amount
        date invoice_date
        enum status "draft..cancelled"
        note rebuilt_2026_07_05 "dropped lease_contract_id FK"
    }

    INVOICE_NOTES {
        int id PK
        int invoice_id FK
        int tenant_id FK
        string note_number UK
        enum type "credit/debit"
        decimal amount
        note rebuilt_2026_07_12 "invoice_id now nullable"
    }

    PAYMENTS {
        int id PK
        int invoice_id FK
        int ewa_bill_id FK
        string payment_number UK
        decimal amount
        string method
        string cheque_number
    }

    EWA_BILLS {
        int id PK
        int lease_contract_id FK
        string bill_number UK
        string billing_period
        decimal elec_consumption
        decimal water_consumption
        decimal ewa_cap
        decimal tenant_portion
        decimal total_amount
        string status
        note dropped_cols "municipality_fee, subsidy removed"
    }

    EWA_PAYMENTS {
        int id PK
        int ewa_bill_id FK
        string payment_number UK
        decimal amount
        string method
        string cheque_number
    }

    EWA_BILL_IMPORT_BATCHES {
        int id PK
        uuid batch_id UK
        json rows
    }

    MAINTENANCE_REQUESTS {
        int id PK
        int building_id FK
        int unit_id FK
        string job_order UK
        string property "denormalized"
        string flat "denormalized"
        enum apartment_status
        enum status "waiting_supervisor..cancelled"
        decimal quotation_1
        decimal quotation_2
        decimal quotation_3
        json job_lines
        note rebuilt_2026_06_02 "status enum widened"
    }

    EXPENSES {
        int id PK
        int building_id FK
        int unit_id FK
        int created_by FK
        string category
        decimal amount
        date expense_date
    }

    REVENUES {
        int id PK
        int building_id FK
        int unit_id FK
        int created_by FK
        string category
        decimal amount
        date revenue_date
    }

    USERS {
        int id PK
        string name UK
        string email UK
        string role "super_admin/admin/user/maintenance"
    }

    FORM_CONFIGS {
        int id PK
        string name
        enum form_type "building/unit"
        enum config_type "form/template"
        json fields
    }

    CUSTOM_FIELD_DEFINITIONS {
        int id PK
        enum form_type
        string name
        string label
        enum field_type
        json options
    }

    AZURE_MAIL_SETTINGS {
        int id PK
        string tenant_id
        string client_id
        string from_address
    }

    AUDIT_LOGS {
        int id PK
        string action
        string entity_type
        int entity_id
        json changes
    }
```
