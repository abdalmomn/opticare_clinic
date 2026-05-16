<?php

namespace App\Modules\MedicalRecords\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MedicalRecordsServiceProvider extends ServiceProvider
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
            ->prefix('api/medical-records')
            ->group(app_path('Modules/MedicalRecords/Routes/api.php'));
    }
}
