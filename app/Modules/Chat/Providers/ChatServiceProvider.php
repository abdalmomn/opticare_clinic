<?php

namespace App\Modules\Chat\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
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
            ->prefix('api/chat')
            ->group(app_path('Modules/Chat/Routes/api.php'));
    }
}
