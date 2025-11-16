<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * 管理者ユーザーを 1 件だけ用意する
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => '管理者',
                'password'          => Hash::make('Admin1234'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}