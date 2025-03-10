<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Creazione utente superadmin
        User::create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);
    }
}
