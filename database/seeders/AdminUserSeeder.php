<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'CosmoViajes',
            'email' => 'cosmosviajes23@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => UserRole::ADMIN,
        ]);        

    }
}