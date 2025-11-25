<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 管理者ユーザー 1件
            AdminUserSeeder::class,

            // 一般ユーザー用ダミーデータ
            UsersTableSeeder::class,

            // 勤怠レコードのダミーデータ
            AttendancesTableSeeder::class,

            // 勤怠休憩レコードのダミーデータ
            AttendanceBreaksTableSeeder::class,

            // 修正申請レコードのダミーデータ（使っているなら）
            StampCorrectionRequestsTableSeeder::class,
        ]);
    }
}