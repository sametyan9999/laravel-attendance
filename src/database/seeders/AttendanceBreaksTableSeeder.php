<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceBreaksTableSeeder extends Seeder
{
    public function run(): void
    {
        // 画面確認用ユーザー
        $staff = DB::table('users')->where('email', 'user1@example.com')->first();

        if (! $staff) {
            return;
        }

        // このユーザーの勤怠（さっきの Seeder で作った10営業日分）
        $attendances = DB::table('attendances')
            ->where('user_id', $staff->id)
            ->orderBy('work_date')
            ->get();

        foreach ($attendances as $attendance) {
            $workDate = Carbon::parse($attendance->work_date);

            DB::table('attendance_breaks')->insert([
                'attendance_id' => $attendance->id,
                'break_in_at'   => $workDate->copy()->setTime(12, 0),
                'break_out_at'  => $workDate->copy()->setTime(13, 0),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}