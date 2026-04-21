<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin account
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('admin123123'),
            'role'     => 'admin',
        ]);

        // Owner account
        User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@example.com',
            'password' => Hash::make('owner123123'),
            'role'     => 'owner',
        ]);

        // Cashier account
        User::create([
            'name'     => 'Cashier User',
            'email'    => 'cashier@example.com',
            'password' => Hash::make('cashier123123'),
            'role'     => 'cashier',
        ]);
    }
}
