<?php

namespace App\Modules\RolesPermissions\Providers;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\MedicalRecord;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use App\Modules\RolesPermissions\Policies\AppointmentPolicy;
use App\Modules\RolesPermissions\Policies\ImagingPolicy;
use App\Modules\RolesPermissions\Policies\MedicalRecordPolicy;
use App\Modules\RolesPermissions\Policies\StaffPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RolesPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
        $this->loadRoutes();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(MedicalRecord::class, MedicalRecordPolicy::class);
        Gate::policy(DoctorPrivateNote::class, MedicalRecordPolicy::class);
        Gate::policy(ImagingRequest::class, ImagingPolicy::class);
        Gate::policy(ImagingFile::class, ImagingPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
    }

    private function registerGates(): void
    {
        Gate::define('can-assign-roles', function (Staff $staff): bool {
            return AccessControlHelper::actorCanManageRoles($staff);
        });

        Gate::define('can-override-permissions', function (Staff $staff): bool {
            return AccessControlHelper::actorCanOverridePermissions($staff);
        });

        Gate::define('view-patient-in-center', function (Staff $staff, int $patientId, bool $isCenter = false): bool {
            if (! $isCenter) {
                return true;
            }
            if ($staff->hasAnyRole([
                RoleEnum::MEDICAL_CENTER_ADMIN->value,
                RoleEnum::SECRETARY->value,
            ], 'api')) {
                return true;
            }
            if ($staff->hasRole(RoleEnum::DOCTOR->value, 'api')) {
                return Appointment::query()
                    ->where('patient_id', $patientId)
                    ->where('doctor_id', $staff->id)
                    ->exists();
            }

            return false;
        });
    }

    private function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/role-permission')
            ->group(app_path('Modules/RolesPermissions/Routes/api.php'));
    }
}
