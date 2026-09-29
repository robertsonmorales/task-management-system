<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Robertson Morales',
            'email' => 'robertsonmorales23@gmail.com',
            'password' => Hash::make('7ujm&UJM'),
            'user_role_id' => 1, // admin role
        ]);

        User::factory()->count(9)->create();
    }
}
