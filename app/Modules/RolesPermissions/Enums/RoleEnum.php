<?php

namespace App\Modules\RolesPermissions\Enums;

/*
======================================
 // CLINIC_ADMIN
 // clinic-level admin has been added for two reasons:
 // if there are two doctor in one clinic, one of them can be assigned as clinic admin to manage staff and settings
 // doctors in medical center can not be clinic admins, only medical center admin can manage their roles and permissions
======================================
*/

enum RoleEnum: string
{
    case MEDICAL_CENTER_ADMIN = 'medical_center_admin';
    case CLINIC_ADMIN         = 'clinic_admin';
    case DOCTOR               = 'doctor';
    case SECRETARY            = 'secretary';
    case IMAGING_TECHNICIAN   = 'imaging_technician';

    // case VISITING_DOCTOR    = 'visiting_doctor';
    // case NURSE              = 'nurse';
    // case ACCOUNTANT         = 'accountant';
    // case RECEPTIONIST       = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::MEDICAL_CENTER_ADMIN => 'Medical Center Admin',
            self::CLINIC_ADMIN         => 'Clinic Admin',
            self::DOCTOR               => 'Doctor',
            self::SECRETARY            => 'Secretary',
            self::IMAGING_TECHNICIAN   => 'Imaging Technician',
        };
    }

    public static function canAssignRoles(): array
    {
        return [
            self::MEDICAL_CENTER_ADMIN->value,
            self::CLINIC_ADMIN->value,
        ];
    }

    public static function adminRoles(): array
    {
        return [
            self::MEDICAL_CENTER_ADMIN->value,
            self::CLINIC_ADMIN->value,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function clinicStaffRoles(): array
    {
        return self::values();
    }
}
