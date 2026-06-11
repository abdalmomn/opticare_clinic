<?php

use App\Modules\Imaging\Controllers\ImagingRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->controller(ImagingRequestController::class)->group(function () {
    Route::get('/requests', 'index');
    Route::post('/requests', 'store');
    Route::get('/requests/{imagingRequest}', 'show');
    Route::post('/requests/{imagingRequest}/cancel', 'cancel');
    Route::post('/requests/{imagingRequest}/confirm-payment', 'confirmPayment');
    Route::post('/requests/{imagingRequest}/send-to-technician', 'sendToTechnician');
});
