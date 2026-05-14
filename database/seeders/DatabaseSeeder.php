<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Manager — full access
        User::firstOrCreate(
            ['email' => 'manager@resto.test'],
            [
                'name'     => 'Manager',
                'password' => Hash::make('password'),
                'role'     => UserRole::Manager,
            ]
        );

        // Cashier — POS access
        User::firstOrCreate(
            ['email' => 'cashier@resto.test'],
            [
                'name'     => 'Cashier',
                'password' => Hash::make('password'),
                'role'     => UserRole::Cashier,
            ]
        );

        // Kitchen — Kitchen Display access
        User::firstOrCreate(
            ['email' => 'kitchen@resto.test'],
            [
                'name'     => 'Kitchen Staff',
                'password' => Hash::make('password'),
                'role'     => UserRole::Kitchen,
            ]
        );
    }
}
