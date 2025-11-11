<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class AttendancesTableSeeder extends Seeder
{
    public function run(): void
    {
        $base = CarbonImmutable::now()->startOfMonth();
        // Staff A: 今月の1〜3日分を作成
        for ($d = 1; $d <= 3; $d++) {
            $date = $base->addDays($d - 1)->toDateString();
            DB::table('attendances')->insert([
                'user_id'      => DB::table('users')->where('email','staff-a@example.com')->value('id'),
                'work_date'    => $date,
                'clock_in_at'  => $base->addDays($d - 1)->setTime(9, 0),
                'clock_out_at' => $base->addDays($d - 1)->setTime(18, 0),
                'note'         => null,
                'status'       => 'completed',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
        // Staff B: 本日だけ出勤中
        DB::table('attendances')->insert([
            'user_id'      => DB::table('users')->where('email','staff-b@example.com')->value('id'),
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now()->copy()->setTime(10, 0),
            'clock_out_at' => null,
            'note'         => '外出予定あり',
            'status'       => 'working',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}