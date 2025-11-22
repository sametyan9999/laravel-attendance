<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    public function run(): void
    {
        $staff = DB::table('users')->where('email', 'user1@example.com')->first();

        if (!$staff) {
            return;
        }

        $date = Carbon::now()->startOfMonth();
        $workDates = [];

        while (count($workDates) < 10) {
            if (!in_array($date->dayOfWeekIso, [6, 7], true)) {
                $workDates[] = $date->copy();
            }
            $date->addDay();
        }

        foreach ($workDates as $d) {
            $workDate = $d->toDateString();

            DB::table('attendances')->insert([
                'user_id' => $staff->id,
                'work_date' => $workDate,
                'clock_in_at' => $d->copy()->setTime(9, 0),
                'clock_out_at' => $d->copy()->setTime(18, 0),
                'note' => null,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}