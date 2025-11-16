<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition()
    {
        $in = Carbon::now()->subHours(2);
        return [
            'attendance_id' => Attendance::factory(),
            'break_in_at'   => $in,
            'break_out_at'  => $in->copy()->addMinutes(30),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}