<?php

namespace App\Modules\RolesPermissions\Seeders;

use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();

        $this->command->info('roels and permissions has been seeded successfully.');
    }

    private function createPermissions(): void
    {
        foreach (PermissionList::all() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('permissions created: '.count(PermissionList::all()));
    }

    private function createRoles(): void
    {
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('roles created: '.count(RoleEnum::cases()));
    }

    private function assignPermissionsToRoles(): void
    {
        foreach ($this->buildRolePermissionMap() as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'api');
            $role->syncPermissions($permissions);
            $this->command->info("  ➜ Permissions assigned to [{$roleName}]: ".count($permissions));
        }
    }

    private function buildRolePermissionMap(): array
    {
        $P = PermissionList::class;

        return [

            RoleEnum::MEDICAL_CENTER_ADMIN->value => [
                $P::VIEW_STAFF,
                $P::CREATE_STAFF,
                $P::EDIT_STAFF,
                $P::TOGGLE_STAFF_STATUS,

                $P::MANAGE_SCHEDULES,
                $P::VIEW_SCHEDULES,
                $P::MANAGE_WORKING_HOURS,
                $P::MANAGE_HOLIDAYS,

                $P::ASSIGN_ROLES,
                $P::REVOKE_ROLES,
                $P::OVERRIDE_PERMISSIONS,

                $P::CREATE_CLINIC_ROOM,
                $P::EDIT_CLINIC_ROOM,
                $P::VIEW_CLINIC_ROOMS,

                $P::CREATE_DEVICE,
                $P::EDIT_DEVICE,
                $P::DELETE_DEVICE,
                $P::TOGGLE_DEVICE_STATUS,
                $P::VIEW_DEVICES,

                $P::EDIT_CENTER_SETTINGS,
                $P::MANAGE_PAYMENT_UNIT,

                $P::VIEW_PATIENTS,
                $P::SEARCH_PATIENT,

                $P::ARCHIVE_PATIENT,
                $P::RESTORE_PATIENT,
                $P::VIEW_ARCHIVED_PATIENTS,

                $P::VIEW_APPOINTMENTS,

                $P::VIEW_STATISTICS,
                $P::VIEW_FINANCIAL_SUMMARY,
                $P::VIEW_ACTIVITY_LOG,

                $P::VIEW_IMAGING_REQUESTS,
                $P::VIEW_ALL_IMAGING_REQUESTS,
                $P::MANAGE_IMAGING_QUEUE,
                $P::DELETE_ANY_IMAGING_FILE,
            ],

            RoleEnum::DOCTOR->value => [
                $P::VIEW_PATIENTS,
                $P::SEARCH_PATIENT,
                $P::CREATE_PATIENT,
                $P::EDIT_PATIENT,
                $P::ARCHIVE_PATIENT,
                $P::RESTORE_PATIENT,
                $P::VIEW_ARCHIVED_PATIENTS,

                $P::VIEW_APPOINTMENTS,
                $P::BOOK_FOLLOW_UP,
                $P::BOOK_SURGERY,

                $P::VIEW_MEDICAL_RECORDS,
                $P::EDIT_MEDICAL_RECORDS,

                $P::VIEW_VISIT_TIMELINE,
                $P::CREATE_VISIT_RECORD,

                $P::VIEW_IMAGING_TIMELINE,
                $P::COMPARE_IMAGES,
                $P::ANNOTATE_IMAGES,

                $P::VIEW_REPORTS,
                $P::CREATE_REPORT,
                $P::PRINT_REPORT,
                $P::EXPORT_REPORT,

                $P::ASSIGN_REPORT_TO_SECRETARY,
                $P::APPROVE_REPORT_DRAFT,

                $P::VIEW_PRESCRIPTIONS,
                $P::CREATE_PRESCRIPTION,
                $P::PRINT_PRESCRIPTION,

                $P::VIEW_MEASUREMENTS,
                $P::CREATE_MEASUREMENT,
                $P::PRINT_MEASUREMENT,

                $P::VIEW_DIAGNOSES,
                $P::CREATE_DIAGNOSIS,

                $P::VIEW_DISEASE_CLASSIFICATION,
                $P::ADD_DISEASE_CLASSIFICATION,

                $P::VIEW_OWN_NOTES,
                $P::CREATE_NOTE,

                $P::CREATE_IMAGING_REQUEST,
                $P::VIEW_OWN_IMAGING_REQUESTS,
                $P::CANCEL_IMAGING_REQUEST,
                $P::UPLOAD_DOCTOR_IMAGING_FILES,
                $P::UPLOAD_EXTERNAL_IMAGING_FILES,

                $P::VIEW_SURGERIES,
                $P::CREATE_SURGERY,

                $P::VIEW_FINANCIAL_SUMMARY,

                $P::VIEW_STATISTICS,

                $P::VIEW_SCHEDULES,

                $P::VIEW_DEVICES,

                $P::EDIT_CENTER_SETTINGS,
            ],

            RoleEnum::SECRETARY->value => [
                $P::VIEW_PATIENTS,
                $P::CREATE_PATIENT,
                $P::EDIT_PATIENT,
                $P::SEARCH_PATIENT,
                $P::ARCHIVE_PATIENT,
                $P::RESTORE_PATIENT,
                $P::VIEW_ARCHIVED_PATIENTS,

                $P::VIEW_APPOINTMENTS,
                $P::CREATE_APPOINTMENT,
                $P::CANCEL_APPOINTMENT,
                $P::CONFIRM_APPOINTMENT,

                $P::MANAGE_PATIENT_STATUS,
                $P::ASSIGN_PATIENT_TO_DOCTOR,

                $P::VIEW_IMAGING_REQUESTS,
                $P::CONFIRM_IMAGING_REQUEST,
                $P::CREATE_IMAGING_REQUEST_FOR_PATIENT,
                $P::CONFIRM_IMAGING_PAYMENT,
                $P::SEND_IMAGING_REQUEST_TO_TECHNICIAN,

                $P::VIEW_SURGERIES,
                $P::CONFIRM_SURGERY,

                $P::VIEW_INVOICES,
                $P::CREATE_INVOICE,
                $P::RECORD_PAYMENT,
                $P::PRINT_INVOICE,

                $P::VIEW_ASSIGNED_REPORTS,
                $P::SUBMIT_REPORT_DRAFT,

                $P::VIEW_DEVICES,
                $P::CREATE_DEVICE,
                $P::EDIT_DEVICE,
                $P::TOGGLE_DEVICE_STATUS,

                $P::VIEW_CLINIC_ROOMS,
                $P::CREATE_CLINIC_ROOM,
                $P::EDIT_CLINIC_ROOM,

                $P::VIEW_STAFF,
                $P::MANAGE_SCHEDULES,
                $P::VIEW_SCHEDULES,
                $P::MANAGE_WORKING_HOURS,

                $P::EDIT_CENTER_SETTINGS,

                $P::VIEW_CONVERSATIONS,
                $P::REPLY_CONVERSATIONS,

                $P::VIEW_REPORTS,
            ],

            RoleEnum::IMAGING_TECHNICIAN->value => [
                $P::VIEW_IMAGING_QUEUE,

                $P::UPLOAD_IMAGING_FILES,
                $P::DELETE_OWN_IMAGING_FILE,

                $P::UPDATE_IMAGING_STATUS,
                $P::START_IMAGING_REQUEST,
                $P::COMPLETE_IMAGING_REQUEST,

                $P::VIEW_PATIENTS,
                $P::SEARCH_PATIENT,

                $P::VIEW_DEVICES,
                $P::VIEW_CLINIC_ROOMS,

                $P::VIEW_SCHEDULES,
            ],
        ];
    }
}
