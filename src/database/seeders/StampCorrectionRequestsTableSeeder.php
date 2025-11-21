<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StampCorrectionRequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $staff = DB::table('users')->where('email', 'user1@example.com')->first();
        $admin = DB::table('users')->where('email', 'admin@example.com')->first();

        if (! $staff || ! $admin) {
            // 必要なユーザーがいなければ何もしない
            return;
        }

        // このユーザーの勤怠を日付順に取得（10営業日分ある想定）
        $attendances = DB::table('attendances')
            ->where('user_id', $staff->id)
            ->orderBy('work_date')
            ->get();

        if ($attendances->count() < 10) {
            // 念のため、10件なければ何もしない
            return;
        }

        // 1日目と10日目
        $first = $attendances[0];
        $tenth = $attendances[9];

        $firstDate = Carbon::parse($first->work_date);
        $tenthDate = Carbon::parse($tenth->work_date);

        // --- 1日目: 承認済み残業申請（18:00→19:00） ---
        DB::table('stamp_correction_requests')->insert([
            'attendance_id'           => $first->id,
            'requested_clock_in_at'   => $firstDate->copy()->setTime(9, 0),   // 出勤はそのまま
            'requested_clock_out_at'  => $firstDate->copy()->setTime(19, 0),  // 1時間延長
            'requested_break_minutes' => 60,                                  // 休憩は1hのまま
            'requested_note'          => '残業対応のため1時間延長',
            'status'                  => 'approved',
            'requested_by'            => $staff->id,
            'approved_by'             => $admin->id,
            'approved_at'             => now(),
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        // --- 10日目: 承認待ち残業申請（18:00→19:00） ---
        DB::table('stamp_correction_requests')->insert([
            'attendance_id'           => $tenth->id,
            'requested_clock_in_at'   => $tenthDate->copy()->setTime(9, 0),
            'requested_clock_out_at'  => $tenthDate->copy()->setTime(19, 0),
            'requested_break_minutes' => 60,
            'requested_note'          => '業務都合による1時間の残業申請',
            'status'                  => 'pending',
            'requested_by'            => $staff->id,
            'approved_by'             => null,
            'approved_at'             => null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }
}