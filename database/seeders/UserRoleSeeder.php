<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserRole::insert([[
            'role_type' => 'Admin',
            'role_description' => 'Should be able to perform all CRUD operations on tasks.',
            'is_active' => true,
            'created_at' => now(),
        ], [
            'role_type' => 'User',
            'role_description' => 'Regular users should be able to: View a list of their tasks, Create a new task, Edit their own tasks, and Delete their own tasks.',
            'is_active' => true,
            'created_at' => now(),
        ]]);
    }
}
