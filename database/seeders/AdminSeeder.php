<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'mokhamediyar@gmail.com'],
            [
                'name' => 'Moka Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
