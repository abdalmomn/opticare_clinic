<?php

namespace App\Modules\RolesPermissions\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Models\StaffClinicRole;

class DemoUsersSeeder extends Seeder
{
    private int $demoClinicId = 1;

    public function run(): void
    {
        foreach ($this->demoUsers() as $userData) {
            $staff = Staff::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            $staff->syncRoles([$userData['role']]);

            StaffClinicRole::updateOrCreate(
                [
                    'staff_id'  => $staff->id,
                    'clinic_id' => $userData['clinic_id'],
                    'role_name' => $userData['role'],
                ],
                [
                    'is_temporary' => false,
                    'expires_at'   => null,
                    'notes'        => $userData['notes'] ?? 'Demo user',
                ]
            );

            $this->command->info(" [{$userData['role']}] → {$userData['email']}");
        }

        $this->command->info('demo users have been seeded successfully.');
    }

    private function demoUsers(): array
    {
        return [
            [
                'name'      => 'Dr. Manager',
                'email'     => 'manager@opticare-clinic.com',
                'password'  => '12345678',
                'role'      => RoleEnum::MEDICAL_CENTER_ADMIN->value,
                'clinic_id' => $this->demoClinicId,
                'notes'     => 'Demo medical center admin — created by super admin',
            ],

            [
                'name'      => 'Dr. Ahmed Hassan',
                'email'     => 'doctor@opticare-clinic.com',
                'password'  => '12345678',
                'role'      => RoleEnum::DOCTOR->value,
                'clinic_id' => $this->demoClinicId,
                'notes'     => 'Demo doctor',
            ],

            [
                'name'      => 'Sara Al-Amin',
                'email'     => 'secretary@opticare-clinic.com',
                'password'  => '12345678',
                'role'      => RoleEnum::SECRETARY->value,
                'clinic_id' => $this->demoClinicId,
                'notes'     => 'Demo secretary',
            ],

            [
                'name'      => 'Khaled Tech',
                'email'     => 'imaging@opticare-clinic.com',
                'password'  => '12345678',
                'role'      => RoleEnum::IMAGING_TECHNICIAN->value,
                'clinic_id' => $this->demoClinicId,
                'notes'     => 'Demo imaging technician — uses Device Integration App',
            ],
        ];
    }
}
