<?php

namespace App\Modules\Patients\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PatientServiceProvider extends ServiceProvider
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
            ->prefix('api/patient')
            ->group(app_path('Modules/Patients/Routes/api.php'));
    }
}
