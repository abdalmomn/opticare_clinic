<?php

use App\Modules\Authentication\Models\Staff;
use Illuminate\Support\Facades\Route;
use App\Modules\RolesPermissions\Controllers\AccessControlController;

Route::controller(AccessControlController::class)
->middleware(['auth:sanctum'])
->group(function () {

    Route::get('/index', 'index')
        ->can('viewAny', Staff::class);

    Route::post('/roles/assign', 'assignRole')
        ->can('can-assign-roles');

    Route::post('/roles/revoke', 'revokeRole')
        ->can('can-assign-roles');

    Route::get('/permissions', 'permissions');

    Route::post('/permissions/grant', 'grantPermission')
        ->can('can-override-permissions');

    Route::post('/permissions/revoke', 'revokePermission')
        ->can('can-override-permissions');

    Route::post('/permissions/clear', 'clearPermissionOverride')
        ->can('can-override-permissions');

    Route::post('/permissions/clearAll', 'clearAllPermissionOverride')
        ->can('can-override-permissions');
});
