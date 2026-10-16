<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Account
        User::create([
            'name' => 'Admin HR',
            'email' => 'admin@moklet.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Job Seeker Account
        User::create([
            'name' => 'John Seeker',
            'email' => 'john@moklet.com',
            'password' => Hash::make('password123'),
            'role' => 'job_seeker',
        ]);
    }
}
