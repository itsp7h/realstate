# Graph Report - realestate  (2026-08-16)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1950 nodes · 4493 edges · 208 communities (152 shown, 56 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 24 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `9e754376`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Support\Carbon
- LeaseContract
- ImportController
- Payment
- EwaBill
- ImportController.php
- MaintenanceRequest
- InvoiceTest
- ReportControllerTest
- TestCase
- NumberToWords
- Illuminate\Http\Request
- Tenant
- Illuminate\Foundation\Http\FormRequest
- EwaBillParser
- InvoiceNote
- User
- PropertyUnit
- Illuminate\Database\Eloquent\Model
- LeasingStatusTool.php
- Illuminate\Database\Migrations\Migration
- Illuminate\Http\RedirectResponse
- PaymentReceiptMail
- BuildingImageTest
- Expense
- Illuminate\Contracts\View\View
- TenantController
- Building
- TenantImportTest
- Floor
- devDependencies
- ExpenseController
- Revenue
- static
- EwaBillController
- ProfitLossServiceTest
- Invoice
- Closure
- AzureMailSetting
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- RevenueTest
- ProfitLossReportTest
- composer.json
- scripts
- CollectionReportTest
- McpCompanyStatusServerTest
- post-create-project-cmd
- RentScheduleServiceTest
- PropertyDataBaseImportTest
- manifest.json
- extra
- UserController
- InvoiceController.php
- CustomFieldDefinition
- require
- BuildingSettingsTest
- RentScheduleReportTest
- AppServiceProvider
- require-dev
- setup
- DatabaseSeeder.php
- config
- SendInvoiceRemindersTest
- bootstrap/app.php
- InvoiceMailTest
- SmartImportUnitFloorTest
- psr-4
- Illuminate\Support\Str
- logging.php
- buildings/index.blade.php
- .status
- Illuminate\Database\Eloquent\Relations\HasManyThrough
- layout.blade.php
- console.php
- global-index.blade.php
- property-units/index.blade.php
- .activeLease
- User.php
- sw.js
- buildings/create.blade.php
- buildings/edit.blade.php
- buildings/show.blade.php
- dashboard.blade.php
- invoice-issued.blade.php
- invoice-overdue-reminder.blade.php
- payment-receipt.blade.php
- ewa-bills/index.blade.php
- summary-results.blade.php
- summary-upload.blade.php
- form-configs/index.blade.php
- lease-contracts/index.blade.php
- property-units/create.blade.php
- property-units/edit.blade.php
- bill-wise-statement.blade.php
- collection.blade.php
- financial-summary.blade.php
- group-ageing.blade.php
- profit-loss.blade.php
- rent-schedule.blade.php
- tenant-ageing.blade.php
- tenant-ledger.blade.php
- tenant-statement.blade.php
- vat-return.blade.php
- tenants/index.blade.php
- tenants/show.blade.php

## God Nodes (most connected - your core abstractions)
1. `Tenant` - 173 edges
2. `Building` - 134 edges
3. `LeaseContract` - 105 edges
4. `Invoice` - 103 edges
5. `PropertyUnit` - 95 edges
6. `TestCase` - 77 edges
7. `EwaBill` - 75 edges
8. `Payment` - 72 edges
9. `MaintenanceRequest` - 63 edges
10. `User` - 58 edges

## Surprising Connections (you probably didn't know these)
- `ProfitLossServiceTest` --references--> `ProfitLossService`  [EXTRACTED]
  tests/Unit/ProfitLossServiceTest.php → app/Services/ProfitLossService.php
- `RentScheduleServiceTest` --references--> `RentScheduleService`  [EXTRACTED]
  tests/Unit/RentScheduleServiceTest.php → app/Services/RentScheduleService.php
- `DashboardAnalyticsServiceTest` --references--> `DashboardAnalyticsService`  [EXTRACTED]
  tests/Unit/DashboardAnalyticsServiceTest.php → app/Services/DashboardAnalyticsService.php
- `DashboardController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DashboardController.php → app/Http/Controllers/Controller.php
- `ReportController` --references--> `CollectionReportService`  [EXTRACTED]
  app/Http/Controllers/ReportController.php → app/Services/CollectionReportService.php

## Import Cycles
- None detected.

## Communities (208 total, 56 thin omitted)

### Community 0 - "Illuminate\Support\Carbon"
Cohesion: 0.08
Nodes (12): UnifiedExport, VatReturnExport, EwaPayment, CollectionReportService, ProfitLossService, Carbon, Carbon, TenantLedgerService (+4 more)

### Community 1 - "LeaseContract"
Cohesion: 0.06
Nodes (6): LeaseContractController, LeaseContract, Illuminate\Http\JsonResponse, LeaseContractImportTest, UploadedFile, LeaseContractTest

### Community 2 - "ImportController"
Cohesion: 0.06
Nodes (9): DataController, UploadedFile, ImportController, Spreadsheet, UploadedFile, AuditLog, Symfony\Component\HttpFoundation\BinaryFileResponse, Symfony\Component\HttpFoundation\StreamedResponse (+1 more)

### Community 4 - "EwaBill"
Cohesion: 0.05
Nodes (3): EwaBill, EwaBillSummaryTest, EwaBillTest

### Community 5 - "ImportController.php"
Cohesion: 0.08
Nodes (17): BuildingsExport, FloorsExport, LeaseContractsExport, Closure, ReportExport, TenantsExport, UnitsExport, VatReturnSheetExport (+9 more)

### Community 6 - "MaintenanceRequest"
Cohesion: 0.07
Nodes (6): DashboardController, MaintenanceRequest, DashboardAnalyticsService, Carbon, MaintenanceRequestTest, DashboardAnalyticsServiceTest

### Community 9 - "TestCase"
Cohesion: 0.08
Nodes (11): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Mail, PromoSeven\Connect\AzureMailer\Transport\AzureTransport, AzureMailerRuntimeOverrideTest, ExampleTest, LeaseContractExportTest (+3 more)

### Community 10 - "NumberToWords"
Cohesion: 0.07
Nodes (9): MoneyFormat, NumberToWords, PHPUnit\Framework\Attributes\DataProvider, PHPUnit\Framework\TestCase, self, ExampleTest, ImportControllerHeaderAliasTest, MoneyFormatTest (+1 more)

### Community 11 - "Illuminate\Http\Request"
Cohesion: 0.16
Nodes (4): Closure, ReportController, Illuminate\Http\Request, Illuminate\Http\Response

### Community 13 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.06
Nodes (8): ApproveMaintenanceRequest, AssessMaintenanceRequest, LoginRequest, StoreLeaseContractRequest, StorePaymentRequest, UpdateBuildingSettingsRequest, UpdateLeaseContractRequest, Illuminate\Foundation\Http\FormRequest

### Community 14 - "EwaBillParser"
Cohesion: 0.11
Nodes (4): EwaBillImportBatch, EwaBillBatchImportService, EwaBillParser, Smalot\PdfParser\Parser

### Community 15 - "InvoiceNote"
Cohesion: 0.07
Nodes (6): InvoiceNoteController, TenantNoteController, StoreInvoiceNoteRequest, InvoiceNote, InvoiceNoteTest, TenantNoteTest

### Community 16 - "User"
Cohesion: 0.10
Nodes (3): User, Illuminate\Foundation\Auth\User, AuthTest

### Community 17 - "PropertyUnit"
Cohesion: 0.09
Nodes (5): PropertyUnitController, StorePropertyUnitRequest, UpdatePropertyUnitRequest, PropertyUnit, Illuminate\Support\Facades\Validator

### Community 18 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (6): auditName(), bootAuditable(), Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, Illuminate\Database\Eloquent\Relations\HasMany

### Community 19 - "LeasingStatusTool.php"
Cohesion: 0.19
Nodes (15): CompanyStatusServer, LeasingStatusTool, MaintenanceStatusTool, PropertyOverviewTool, ReceivablesStatusTool, Illuminate\Contracts\JsonSchema\JsonSchema, Laravel\Mcp\Request, Laravel\Mcp\Response (+7 more)

### Community 21 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.06
Nodes (12): AdminController, LoginController, AzureMailSettingController, BuildingImageController, Controller, EwaBillSummaryController, UpdateAzureMailSettingRequest, Illuminate\Http\RedirectResponse (+4 more)

### Community 22 - "PaymentReceiptMail"
Cohesion: 0.20
Nodes (10): InvoiceIssuedMail, InvoiceOverdueReminderMail, PaymentReceiptMail, Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Mail\Mailables\Attachment, Illuminate\Mail\Mailables\Content, Illuminate\Mail\Mailables\Envelope (+2 more)

### Community 23 - "BuildingImageTest"
Cohesion: 0.15
Nodes (3): BuildingImage, Illuminate\Support\Facades\Storage, BuildingImageTest

### Community 25 - "Illuminate\Contracts\View\View"
Cohesion: 0.10
Nodes (6): MaintenanceRequestController, RevenueController, StoreMaintenanceRequest, UpdateMaintenanceRequest, Illuminate\Contracts\View\View, Illuminate\Validation\Rule

### Community 26 - "TenantController"
Cohesion: 0.11
Nodes (4): TenantController, StoreTenantRequest, UpdateTenantRequest, RentScheduleService

### Community 27 - "Building"
Cohesion: 0.13
Nodes (3): Building, BuildingDashboardTest, DashboardControllerTest

### Community 29 - "Floor"
Cohesion: 0.13
Nodes (3): FloorController, Floor, Illuminate\Database\Eloquent\Builder

### Community 30 - "devDependencies"
Cohesion: 0.10
Nodes (19): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+11 more)

### Community 31 - "ExpenseController"
Cohesion: 0.13
Nodes (3): ExpenseController, StoreExpenseRequest, UpdateExpenseRequest

### Community 32 - "Revenue"
Cohesion: 0.12
Nodes (3): StoreRevenueRequest, UpdateRevenueRequest, Revenue

### Community 33 - "static"
Cohesion: 0.09
Nodes (7): FormConfigController, FormConfig, FormConfigService, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\View\View, static

### Community 34 - "EwaBillController"
Cohesion: 0.09
Nodes (4): EwaBillController, StoreEwaBillRequest, StoreEwaPaymentRequest, Barryvdh\DomPDF\Facade\Pdf

### Community 36 - "Invoice"
Cohesion: 0.10
Nodes (5): SendInvoiceReminders, PaymentController, Invoice, TenantMailer, Illuminate\Console\Command

### Community 37 - "Closure"
Cohesion: 0.22
Nodes (7): EnsureUserHasRole, RestrictDestructiveActions, RestrictMaintenanceRole, VerifyMcpToken, Closure, Laravel\Mcp\Facades\Mcp, Symfony\Component\HttpFoundation\Response

### Community 43 - "composer.json"
Cohesion: 0.13
Nodes (14): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+6 more)

### Community 44 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 46 - "McpCompanyStatusServerTest"
Cohesion: 0.22
Nodes (3): Illuminate\Support\Facades\Config, TestResponse, McpCompanyStatusServerTest

### Community 47 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 49 - "PropertyDataBaseImportTest"
Cohesion: 0.24
Nodes (3): PhpOffice\PhpSpreadsheet\Spreadsheet, PhpOffice\PhpSpreadsheet\Writer\Xlsx, PropertyDataBaseImportTest

### Community 50 - "manifest.json"
Cohesion: 0.17
Nodes (11): background_color, description, display, icons, id, name, orientation, scope (+3 more)

### Community 51 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 54 - "InvoiceController.php"
Cohesion: 0.09
Nodes (5): InvoiceController, GenerateMonthlyInvoicesRequest, StoreInvoiceRequest, UpdateInvoiceRequest, Illuminate\Contracts\Validation\ValidationRule

### Community 56 - "CustomFieldDefinition"
Cohesion: 0.11
Nodes (4): BuildingController, StoreBuildingRequest, UpdateBuildingRequest, CustomFieldDefinition

### Community 57 - "require"
Cohesion: 0.22
Nodes (9): require, barryvdh/laravel-dompdf, laravel/framework, laravel/mcp, laravel/tinker, maatwebsite/excel, php, promoseven/connect (+1 more)

### Community 62 - "AppServiceProvider"
Cohesion: 0.32
Nodes (3): AppServiceProvider, Illuminate\Support\ServiceProvider, Throwable

### Community 63 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 64 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 65 - "DatabaseSeeder.php"
Cohesion: 0.36
Nodes (4): DatabaseSeeder, PropertyUnitSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 66 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 69 - "bootstrap/app.php"
Cohesion: 0.33
Nodes (4): Illuminate\Console\Scheduling\Schedule, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 73 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 74 - "Illuminate\Support\Str"
Cohesion: 0.22
Nodes (3): CustomFieldController, Illuminate\Support\Str, Pdo\Mysql

### Community 75 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 76 - "buildings/index.blade.php"
Cohesion: 0.50
Nodes (3): components.expense-sheet, components.import-modal, components.import-result

## Knowledge Gaps
- **109 isolated node(s):** `emails.partials.footer`, `emails.partials.header`, `components.import-modal`, `components.import-result`, `components.import-modal` (+104 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **56 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Tenant` connect `Tenant` to `Illuminate\Support\Carbon`, `LeaseContract`, `ImportController`, `Payment`, `ImportController.php`, `MaintenanceRequest`, `InvoiceTest`, `ReportControllerTest`, `TestCase`, `Illuminate\Http\Request`, `InvoiceNote`, `User`, `PropertyUnit`, `Illuminate\Database\Eloquent\Model`, `LeasingStatusTool.php`, `Illuminate\Contracts\View\View`, `TenantController`, `Building`, `TenantImportTest`, `Floor`, `static`, `ProfitLossServiceTest`, `ProfitLossReportTest`, `CollectionReportTest`, `RentScheduleServiceTest`, `PropertyDataBaseImportTest`, `InvoiceController.php`, `RentScheduleReportTest`, `SendInvoiceRemindersTest`, `InvoiceMailTest`, `Illuminate\Database\Eloquent\Relations\HasManyThrough`, `.activeLease`?**
  _High betweenness centrality (0.131) - this node is a cross-community bridge._
- **Why does `AzureMailSetting` connect `AzureMailSetting` to `TestCase`, `NumberToWords`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\RedirectResponse`, `AppServiceProvider`?**
  _High betweenness centrality (0.103) - this node is a cross-community bridge._
- **Why does `Building` connect `Building` to `Illuminate\Support\Carbon`, `LeaseContract`, `ImportController`, `ImportController.php`, `MaintenanceRequest`, `InvoiceTest`, `ReportControllerTest`, `TestCase`, `Illuminate\Http\Request`, `PropertyUnit`, `Illuminate\Database\Eloquent\Model`, `LeasingStatusTool.php`, `Illuminate\Http\RedirectResponse`, `BuildingImageTest`, `Expense`, `Illuminate\Contracts\View\View`, `Floor`, `ExpenseController`, `ProfitLossServiceTest`, `Invoice`, `RevenueTest`, `ProfitLossReportTest`, `McpCompanyStatusServerTest`, `PropertyDataBaseImportTest`, `CustomFieldDefinition`, `BuildingSettingsTest`, `SmartImportUnitFloorTest`?**
  _High betweenness centrality (0.099) - this node is a cross-community bridge._
- **What connects `emails.partials.footer`, `emails.partials.header`, `components.import-modal` to the rest of the system?**
  _109 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Support\Carbon` be split into smaller, more focused modules?**
  _Cohesion score 0.08013468013468013 - nodes in this community are weakly interconnected._
- **Should `LeaseContract` be split into smaller, more focused modules?**
  _Cohesion score 0.062206572769953054 - nodes in this community are weakly interconnected._
- **Should `ImportController` be split into smaller, more focused modules?**
  _Cohesion score 0.05970149253731343 - nodes in this community are weakly interconnected._