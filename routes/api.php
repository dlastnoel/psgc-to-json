<?php

use App\Http\Controllers\Psgc\BarangayController;
use App\Http\Controllers\Psgc\CityMunicipalityController;
use App\Http\Controllers\Psgc\ProvinceController;
use App\Http\Controllers\Psgc\RegionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// PSGC API Routes
Route::prefix('psgc')->group(function () {
    // Regions
    Route::apiResource('regions', RegionController::class)->only(['index', 'show']);

    // Provinces
    Route::apiResource('provinces', ProvinceController::class)->only(['index', 'show']);

    // Cities/Municipalities
    Route::prefix('cities-municipalities')->group(function () {
        Route::get('/', [CityMunicipalityController::class, 'index']);
        Route::get('/{cityMunicipality}', [CityMunicipalityController::class, 'show']);
    });

    // Barangays
    Route::apiResource('barangays', BarangayController::class)->only(['index', 'show']);
});
