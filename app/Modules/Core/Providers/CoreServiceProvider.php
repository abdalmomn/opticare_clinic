<?php

namespace App\Modules\Core\Providers;

use App\Modules\Authentication\Providers\AuthenticationServiceProvider;
use App\Modules\Appointments\Providers\AppointmentsServiceProvider;
use App\Modules\MedicalRecords\Providers\MedicalRecordsServiceProvider;
use App\Modules\Imaging\Providers\ImagingServiceProvider;
use App\Modules\Payments\Providers\PaymentsServiceProvider;
use App\Modules\Scheduling\Providers\SchedulingServiceProvider;
use App\Modules\Chat\Providers\ChatServiceProvider;
use App\Modules\Notifications\Providers\NotificationsServiceProvider;
use App\Modules\Clinic\Providers\ClinicServiceProvider;
use App\Modules\Patients\Providers\PatientServiceProvider;
use App\Modules\RolesPermissions\Providers\RolesPermissionsServiceProvider;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AuthenticationServiceProvider::class);
        $this->app->register(ClinicServiceProvider::class);
        $this->app->register(AppointmentsServiceProvider::class);
        $this->app->register(MedicalRecordsServiceProvider::class);
        $this->app->register(ImagingServiceProvider::class);
        $this->app->register(PaymentsServiceProvider::class);
        $this->app->register(SchedulingServiceProvider::class);
        $this->app->register(ChatServiceProvider::class);
        $this->app->register(NotificationsServiceProvider::class);
        $this->app->register(RolesPermissionsServiceProvider::class);
        $this->app->register(PatientServiceProvider::class);
    }

    public function boot(): void {}
}
