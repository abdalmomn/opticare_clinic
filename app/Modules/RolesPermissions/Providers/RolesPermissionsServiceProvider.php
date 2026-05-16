<?php

namespace App\Modules\RolesPermissions\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RolesPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/roles-permissions')
            ->group(app_path('Modules/RolesPermissions/Routes/api.php'));
    }
}
