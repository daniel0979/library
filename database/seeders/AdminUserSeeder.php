<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $memberRole = Role::where('name', 'member')->first();

        User::updateOrCreate(
            ['email' => 'admin@library.local'],
            [
                'name' => 'Library Admin',
                'phone' => '0000000000',
                'password' => 'password',
                'status' => 'active',
                'role_id' => $adminRole?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member@library.local'],
            [
                'name' => 'Demo Member',
                'phone' => '1111111111',
                'password' => 'password',
                'status' => 'active',
                'role_id' => $memberRole?->id,
            ]
        );
    }
}
