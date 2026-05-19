<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyUnitController;
use App\Http\Controllers\BuildingController;

Route::get('/', fn() => redirect()->route('property-units.index'));

Route::get('/dashboard', fn() => redirect()->route('property-units.index'))->name('dashboard');

Route::get('/property-units/export', [PropertyUnitController::class, 'export'])->name('property-units.export');
Route::resource('property-units', PropertyUnitController::class);

Route::resource('buildings', BuildingController::class);
