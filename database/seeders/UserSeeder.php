<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the six default employee accounts.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Rina', 'email' => 'admin@printflow.test', 'role' => User::ROLE_ADMIN],
            ['name' => 'Budi', 'email' => 'desainer@printflow.test', 'role' => User::ROLE_DESAINER],
            ['name' => 'Agus', 'email' => 'operator@printflow.test', 'role' => User::ROLE_OPERATOR_CETAK],
            ['name' => 'Dedi', 'email' => 'finishing@printflow.test', 'role' => User::ROLE_FINISHING],
            ['name' => 'Eko', 'email' => 'advertising@printflow.test', 'role' => User::ROLE_ADVERTISING],
            ['name' => 'Pak Hendra', 'email' => 'owner@printflow.test', 'role' => User::ROLE_OWNER],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
