<?php

namespace App\Modules\RolesPermissions\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\MedicalRecords\Models\MedicalRecord;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\Imaging\Models\ImagingRequest;

use App\Modules\RolesPermissions\Enums\RoleEnum;

use App\Modules\RolesPermissions\Policies\AppointmentPolicy;
use App\Modules\RolesPermissions\Policies\MedicalRecordPolicy;
use App\Modules\RolesPermissions\Policies\ImagingPolicy;
use App\Modules\RolesPermissions\Policies\StaffPolicy;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
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
            // if it's not center-level access, any staff can view the patient, because they are in the same clinic and have access to the same patients
            if (!$isCenter) {
                return true;
            }
            // if user is secretary, he can view all patients in his clinic
            // if user is center-level admin, he can view all patients in the center
            if ($staff->hasAnyRole([
                RoleEnum::MEDICAL_CENTER_ADMIN->value,
                RoleEnum::SECRETARY->value,
            ], 'api')) {
                return true;
            }

            // if user is doctor he can view his patients only, so we check if there is an appointment between the doctor and the patient, if there is an appointment, then the doctor can view the patient
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
