<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // ★ migrate:fresh 後はテーブルが空なので truncate 不要
        // DB::table('users')->truncate(); ← これは削除してOK

        DB::table('users')->insert([
            [
                'name'       => 'Admin User',
                'email'      => 'admin@example.com',
                // 管理者ログイン用パスワード（テスト仕様に合わせて変えてもOK）
                'password'   => Hash::make('Admin1234'),
                'role'       => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}