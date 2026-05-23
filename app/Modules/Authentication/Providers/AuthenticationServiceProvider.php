<?php

namespace App\Modules\Authentication\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Modules\Authentication\Events\StaffPasswordResetOtpRequested;
use App\Modules\Authentication\Listeners\DispatchStaffPasswordResetOtpMailJob;
class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerEvents();
    }

    private function registerEvents(): void
    {
        Event::listen(
            StaffPasswordResetOtpRequested::class,
            DispatchStaffPasswordResetOtpMailJob::class
        );
    }

    protected function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/auth')
            ->group(app_path('Modules/Authentication/Routes/api.php'));
    }
}
