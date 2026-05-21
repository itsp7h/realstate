<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyUnitController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\FormConfigController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\TenantController;

// Unified data import/export
Route::get('/data',                      [DataController::class, 'index'])->name('data.index');
Route::get('/data/template/{format?}',   [DataController::class, 'template'])->name('data.template');
Route::get('/data/export',               [DataController::class, 'export'])->name('data.export');
Route::post('/data/import',              [DataController::class, 'import'])->name('data.import');

Route::get('/import/template/{type}/{format?}', [ImportController::class, 'template'])->name('import.template');
Route::post('/import/buildings', [ImportController::class, 'buildings'])->name('import.buildings');
Route::post('/import/floors',    [ImportController::class, 'floors'])->name('import.floors');
Route::post('/import/units',     [ImportController::class, 'units'])->name('import.units');
Route::post('/import/contracts', [ImportController::class, 'contracts'])->name('import.contracts');

Route::get('/export/buildings', [ImportController::class, 'exportBuildings'])->name('export.buildings');
Route::get('/export/floors',    [ImportController::class, 'exportFloors'])->name('export.floors');
Route::get('/export/units',     [ImportController::class, 'exportUnits'])->name('export.units');

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/property-units/export', [PropertyUnitController::class, 'export'])->name('property-units.export');
Route::get('/property-units/building/{building}/data', [PropertyUnitController::class, 'buildingData'])->name('property-units.building-data');
Route::get('/property-units/building/{building}/floors', [PropertyUnitController::class, 'floorsByBuilding'])->name('property-units.building-floors');
Route::resource('property-units', PropertyUnitController::class);

Route::resource('tenants', TenantController::class);

// Lease Contracts (placeholder index — full CRUD to be added)
Route::get('/lease-contracts', fn() => redirect()->route('dashboard'))->name('lease-contracts.index');

Route::resource('buildings', BuildingController::class);
Route::get('/floors', [FloorController::class, 'globalIndex'])->name('floors.global');
Route::resource('buildings.floors', FloorController::class)->shallow()->except(['show']);

Route::post('/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');

Route::get('/form-configs', [FormConfigController::class, 'index'])->name('form-configs.index');
Route::get('/form-configs/{formType}/{configType}/edit', [FormConfigController::class, 'edit'])->name('form-configs.edit');
Route::put('/form-configs/{formType}/{configType}', [FormConfigController::class, 'update'])->name('form-configs.update');
