<?php

namespace App\Modules\Imaging\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImagingServiceProvider extends ServiceProvider
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
            ->prefix('api/imaging')
            ->group(app_path('Modules/Imaging/Routes/api.php'));
    }
}
