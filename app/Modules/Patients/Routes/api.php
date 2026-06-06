<?php

use App\Modules\Patients\Controllers\PatientController;
use Illuminate\Support\Facades\Route;


Route::controller(PatientController::class)
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::get('/search', 'search');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{patient}', 'show');
        Route::post('/{patient}', 'update');
        Route::patch('/{patient}/toggle-status', 'toggleStatus');

        Route::patch('/{patient}/archive', 'archive');
        Route::patch('/{patient}/restore', 'restore');
        Route::patch('/{patient}/mark-deceased', 'markDeceased');
});
