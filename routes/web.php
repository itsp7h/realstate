<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyUnitController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FormConfigController;
use App\Http\Controllers\CustomFieldController;

Route::get('/', fn() => redirect()->route('property-units.index'));

Route::get('/dashboard', fn() => redirect()->route('property-units.index'))->name('dashboard');

Route::get('/property-units/export', [PropertyUnitController::class, 'export'])->name('property-units.export');
Route::get('/property-units/building/{building}/data', [PropertyUnitController::class, 'buildingData'])->name('property-units.building-data');
Route::resource('property-units', PropertyUnitController::class);

Route::resource('buildings', BuildingController::class);

Route::post('/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');

Route::get('/form-configs', [FormConfigController::class, 'index'])->name('form-configs.index');
Route::get('/form-configs/{formType}/{configType}/edit', [FormConfigController::class, 'edit'])->name('form-configs.edit');
Route::put('/form-configs/{formType}/{configType}', [FormConfigController::class, 'update'])->name('form-configs.update');
