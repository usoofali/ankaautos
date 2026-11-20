<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User $admin */
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@ankaautos.com'],
            [
                'name' => 'Anka Super Administrator',
                'phone' => '+234 803 000 0000',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->hasRole(SystemRole::SuperAdministrator->value)) {
            $admin->assignRole(SystemRole::SuperAdministrator->value);
        }
    }
}
