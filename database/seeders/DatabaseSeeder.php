<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\RolesPermissions\Seeders\RbacDatabaseSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RbacDatabaseSeeder::class,
        ]);
    }
}
