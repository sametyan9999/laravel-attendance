<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceBreaksTableSeeder extends Seeder
{
    public function run(): void
    {
        // Staff A の 1日目の勤怠に 12:00-13:00 の休憩
        $attendanceId = DB::table('attendances')
            ->orderBy('id')
            ->first()->id ?? null;

        if ($attendanceId) {
            $in  = now()->copy()->setTime(12, 0)->subDays(2);
            $out = now()->copy()->setTime(13, 0)->subDays(2);

            DB::table('attendance_breaks')->insert([
                'attendance_id' => $attendanceId,
                'break_in_at'   => $in,
                'break_out_at'  => $out,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}