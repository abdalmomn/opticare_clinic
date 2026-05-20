<?php

use App\Modules\Authentication\Models\Staff;
use Illuminate\Support\Facades\Route;
use App\Modules\RolesPermissions\Controllers\AccessControlController;

Route::middleware(['auth:sanctum'])
->controller(AccessControlController::class)
->group(function () {

    Route::get('/', 'index')
        ->can('viewAny', Staff::class);

    Route::post('/assign', 'assign')
        ->can('can-assign-roles');

    Route::post('/revoke', 'revoke')
        ->can('can-assign-roles');

    Route::get('/permissions', 'index');
});
