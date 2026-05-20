<?php

namespace App\Modules\RolesPermissions\Seeders;

use Illuminate\Database\Seeder;

class RbacDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
