<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Authentication\Controllers\AuthController;
use App\Modules\Authentication\Controllers\PasswordResetController;
use App\Modules\Authentication\Controllers\StaffAccountController;


Route::post('/login', [AuthController::class, 'login']);

Route::prefix('forgot-password')
->controller(PasswordResetController::class)->group(function () {
    Route::post('/send-code', 'sendCode');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset', 'reset');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/staff', [StaffAccountController::class, 'store']);

});
