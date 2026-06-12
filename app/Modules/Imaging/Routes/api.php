<?php

use App\Modules\Imaging\Controllers\DirectImagingUploadController;
use App\Modules\Imaging\Controllers\ImagingActivityLogController;
use App\Modules\Imaging\Controllers\ImagingDeviceController;
use App\Modules\Imaging\Controllers\ImagingFileController;
use App\Modules\Imaging\Controllers\ImagingRequestController;
use App\Modules\Imaging\Controllers\ImagingStatisticsController;
use App\Modules\Imaging\Controllers\ImagingTechnicianController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(ImagingRequestController::class)->group(function () {
        Route::get('/requests', 'index');
        Route::post('/requests', 'store');
        Route::get('/requests/{imagingRequest}', 'show');
        Route::post('/requests/{imagingRequest}/cancel', 'cancel');
        Route::post('/requests/{imagingRequest}/confirm-payment', 'confirmPayment');
        Route::post('/requests/{imagingRequest}/send-to-technician', 'sendToTechnician');
    });

    Route::controller(ImagingTechnicianController::class)->group(function () {
        Route::get('/technician/requests', 'queue');
        Route::post('/requests/{imagingRequest}/start', 'start');
        Route::post('/requests/{imagingRequest}/complete', 'complete');
    });

    Route::controller(ImagingFileController::class)->group(function () {
        Route::post('/requests/{imagingRequest}/files', 'store');
        Route::delete('/files/{imagingFile}', 'destroy');
    });

    Route::controller(DirectImagingUploadController::class)->group(function () {
        Route::post('/direct-upload', 'directUpload');
        Route::post('/external-upload', 'externalUpload');
    });

    Route::controller(ImagingDeviceController::class)->group(function () {
        Route::get('/devices', 'index');
        Route::post('/devices', 'store');
        Route::get('/devices/{device}', 'show');
        Route::patch('/devices/{device}', 'update');
        Route::patch('/devices/{device}/toggle-status', 'toggleStatus');
        Route::delete('/devices/{device}', 'destroy');
    });

    Route::controller(ImagingStatisticsController::class)->group(function () {
        Route::get('/statistics/overview', 'overview');
        Route::get('/statistics/by-device', 'byDevice');
        Route::get('/statistics/by-type', 'byType');
    });

    Route::get('/activity-logs', [ImagingActivityLogController::class, 'index']);
});
