<?php

use App\Modules\Appointments\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::controller(AppointmentController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        // Static routes must come before dynamic appointment route to avoid conflicts
        Route::get('/today', 'today');
        Route::get('/queue', 'queue');
        Route::get('/doctor/today', 'doctorToday');

        Route::get('/', 'index');
        Route::post('/', 'store');

        Route::get('/{appointment}', 'show');
        Route::post('/{appointment}', 'update');
        Route::post('/{appointment}/confirm', 'confirm');
        Route::post('/{appointment}/cancel', 'cancel');
        Route::post('/{appointment}/check-in', 'checkIn');
        Route::post('/{appointment}/assign-doctor', 'assignDoctor');
        Route::post('/{appointment}/start', 'start');
        Route::post('/{appointment}/complete', 'complete');
    });
