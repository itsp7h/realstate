<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyUnitController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\FormConfigController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\LeaseContractController;
use App\Http\Controllers\BuildingImageController;
use App\Http\Controllers\EwaBillController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceNoteController;
use App\Http\Controllers\TenantNoteController;
use App\Http\Controllers\ReportController;

// Unified data import/export
Route::get('/data',                      [DataController::class, 'index'])->name('data.index');
Route::get('/data/template/{format?}',   [DataController::class, 'template'])->name('data.template');
Route::get('/data/export',               [DataController::class, 'export'])->name('data.export');
Route::post('/data/import',              [DataController::class, 'import'])->name('data.import');

Route::get('/import/template/{type}/{format?}', [ImportController::class, 'template'])->name('import.template');
Route::post('/import/buildings', [ImportController::class, 'buildings'])->name('import.buildings');
Route::post('/import/floors',    [ImportController::class, 'floors'])->name('import.floors');
Route::post('/import/units',     [ImportController::class, 'units'])->name('import.units');
Route::post('/import/tenants',   [ImportController::class, 'tenants'])->name('import.tenants');
Route::post('/import/contracts', [ImportController::class, 'contracts'])->name('import.contracts');
Route::post('/import/smart',    [ImportController::class, 'smart'])->name('import.smart');

Route::get('/export/buildings', [ImportController::class, 'exportBuildings'])->name('export.buildings');
Route::get('/export/floors',    [ImportController::class, 'exportFloors'])->name('export.floors');
Route::get('/export/units',     [ImportController::class, 'exportUnits'])->name('export.units');
Route::get('/export/tenants',   [ImportController::class, 'exportTenants'])->name('export.tenants');
Route::get('/export/contracts', [ImportController::class, 'exportContracts'])->name('export.contracts');

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/property-units/export', [PropertyUnitController::class, 'export'])->name('property-units.export');
Route::get('/property-units/building/{building}/data', [PropertyUnitController::class, 'buildingData'])->name('property-units.building-data');
Route::get('/property-units/building/{building}/floors', [PropertyUnitController::class, 'floorsByBuilding'])->name('property-units.building-floors');
Route::resource('property-units', PropertyUnitController::class);

Route::get('/tenants/search', [TenantController::class, 'search'])->name('tenants.search');
Route::resource('tenants', TenantController::class);
Route::post('/tenants/{tenant}/notes',                 [TenantNoteController::class, 'store'])->name('tenants.notes.store');
Route::delete('/tenants/{tenant}/notes/{invoiceNote}', [TenantNoteController::class, 'destroy'])->name('tenants.notes.destroy');

Route::resource('maintenance', MaintenanceRequestController::class)
    ->parameters(['maintenance' => 'maintenanceRequest']);
Route::post('/maintenance/{maintenanceRequest}/assess',  [MaintenanceRequestController::class, 'assess'])->name('maintenance.assess');
Route::post('/maintenance/{maintenanceRequest}/approve', [MaintenanceRequestController::class, 'approve'])->name('maintenance.approve');

Route::get('/lease-contracts/search', [LeaseContractController::class, 'search'])->name('lease-contracts.search');
Route::get('/lease-contracts/tenant/{tenant}/active', [LeaseContractController::class, 'activeForTenant'])->name('lease-contracts.active-for-tenant');
Route::get('/lease-contracts/tenant/{tenant}/search', [LeaseContractController::class, 'searchForTenant'])->name('lease-contracts.search-for-tenant');
Route::resource('lease-contracts', LeaseContractController::class);

// Accounting
Route::post('/invoices/generate-monthly', [InvoiceController::class, 'generateMonthly'])->name('invoices.generate-monthly');
Route::resource('invoices', InvoiceController::class);
Route::get('/invoices/{invoice}/pdf',         [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::get('/invoices/{invoice}/pdf/preview', [InvoiceController::class, 'pdfPreview'])->name('invoices.pdf.preview');
Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('/ewa-bills/parse-import',             [EwaBillController::class, 'parseImport'])->name('ewa-bills.parse-import');
Route::resource('ewa-bills', EwaBillController::class);
Route::post('/ewa-bills/{ewaBill}/payments',           [EwaBillController::class, 'storePayment'])->name('ewa-bills.payments.store');
Route::delete('/ewa-bills/{ewaBill}/payments/{ewaPayment}', [EwaBillController::class, 'destroyPayment'])->name('ewa-bills.payments.destroy');
Route::get('/ewa-bills/{ewaBill}/pdf',                 [EwaBillController::class, 'pdf'])->name('ewa-bills.pdf');
Route::get('/ewa-bills/{ewaBill}/pdf/preview',         [EwaBillController::class, 'pdfPreview'])->name('ewa-bills.pdf.preview');
Route::post('/invoices/{invoice}/payments',                   [PaymentController::class, 'store'])->name('invoices.payments.store');
Route::delete('/invoices/{invoice}/payments/{payment}',       [PaymentController::class, 'destroy'])->name('invoices.payments.destroy');
Route::post('/invoices/{invoice}/notes',                      [InvoiceNoteController::class, 'store'])->name('invoices.notes.store');
Route::delete('/invoices/{invoice}/notes/{invoiceNote}',      [InvoiceNoteController::class, 'destroy'])->name('invoices.notes.destroy');

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/tenant-statement',       [ReportController::class, 'tenantStatement'])->name('reports.tenant-statement');
Route::get('/reports/tenant-statement/pdf',   [ReportController::class, 'tenantStatementPdf'])->name('reports.tenant-statement.pdf');
Route::get('/reports/tenant-ledger',          [ReportController::class, 'tenantLedger'])->name('reports.tenant-ledger');
Route::get('/reports/tenant-ledger/pdf',      [ReportController::class, 'tenantLedgerPdf'])->name('reports.tenant-ledger.pdf');
Route::get('/reports/tenant-ageing',          [ReportController::class, 'tenantAgeing'])->name('reports.tenant-ageing');
Route::get('/reports/tenant-ageing/pdf',      [ReportController::class, 'tenantAgeingPdf'])->name('reports.tenant-ageing.pdf');
Route::get('/reports/group-ageing',           [ReportController::class, 'groupAgeing'])->name('reports.group-ageing');
Route::get('/reports/group-ageing/pdf',       [ReportController::class, 'groupAgeingPdf'])->name('reports.group-ageing.pdf');
Route::get('/reports/profit-loss',            [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
Route::get('/reports/profit-loss/pdf',        [ReportController::class, 'profitLossPdf'])->name('reports.profit-loss.pdf');
Route::get('/reports/rent-schedule',          [ReportController::class, 'rentSchedule'])->name('reports.rent-schedule');
Route::get('/reports/rent-schedule/pdf',      [ReportController::class, 'rentSchedulePdf'])->name('reports.rent-schedule.pdf');
Route::get('/invoices/{invoice}/payments/{payment}/receipt',  [PaymentController::class, 'receipt'])->name('invoices.payments.receipt');

Route::resource('buildings', BuildingController::class);
Route::post('/buildings/{building}/images',                        [BuildingImageController::class, 'store'])->name('buildings.images.store');
Route::delete('/buildings/{building}/images/{image}',              [BuildingImageController::class, 'destroy'])->name('buildings.images.destroy');
Route::post('/buildings/{building}/images/reorder',                [BuildingImageController::class, 'reorder'])->name('buildings.images.reorder');
Route::put('/buildings/{building}/settings', [BuildingController::class, 'updateSettings'])->name('buildings.settings.update');
Route::get('/floors', [FloorController::class, 'globalIndex'])->name('floors.global');
Route::resource('buildings.floors', FloorController::class)->shallow()->except(['show']);

Route::post('/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');

// Admin
Route::get('/admin/audit-log',         [AdminController::class, 'auditLog'])->name('admin.audit-log');
Route::delete('/admin/audit-log',      [AdminController::class, 'clearAuditLog'])->name('admin.audit-log.clear');
Route::get('/admin/error-log',         [AdminController::class, 'errorLog'])->name('admin.error-log');
Route::delete('/admin/error-log',      [AdminController::class, 'clearErrorLog'])->name('admin.error-log.clear');

Route::get('/form-configs', [FormConfigController::class, 'index'])->name('form-configs.index');
Route::get('/form-configs/{formType}/{configType}/edit', [FormConfigController::class, 'edit'])->name('form-configs.edit');
Route::put('/form-configs/{formType}/{configType}', [FormConfigController::class, 'update'])->name('form-configs.update');
