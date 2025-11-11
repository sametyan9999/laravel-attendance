<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $staffA   = DB::table('users')->where('email','staff-a@example.com')->first();
        $admin    = DB::table('users')->where('email','admin@example.com')->first();
        $targetAt = DB::table('attendances')->where('user_id', $staffA->id)->orderByDesc('id')->first();

        if ($targetAt) {
            // 承認待ち
            DB::table('stamp_correction_requests')->insert([
                'attendance_id'            => $targetAt->id,
                'requested_clock_in_at'    => null,
                'requested_clock_out_at'   => null,
                'requested_break_minutes'  => 90, // 休憩を1.5hに変更申請
                'requested_note'           => '通院のため休憩延長',
                'status'                   => 'pending',
                'requested_by'             => $staffA->id,
                'approved_by'              => null,
                'approved_at'              => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            // 承認済み
            DB::table('stamp_correction_requests')->insert([
                'attendance_id'            => $targetAt->id,
                'requested_clock_in_at'    => now()->copy()->setTime(9, 30)->subDays(1),
                'requested_clock_out_at'   => now()->copy()->setTime(18, 30)->subDays(1),
                'requested_break_minutes'  => 60,
                'requested_note'           => '電車遅延のため出勤遅れ',
                'status'                   => 'approved',
                'requested_by'             => $staffA->id,
                'approved_by'              => $admin->id,
                'approved_at'              => now()->subDays(1),
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }
    }
}