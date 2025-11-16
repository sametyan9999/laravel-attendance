<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = Carbon::today()->toDateString();

        return [
            'user_id'       => User::factory(),
            'work_date'     => $date,
            'clock_in_at'   => null,
            'clock_out_at'  => null,
            'status'        => 'off_duty',
            'note'          => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }

    public function working()
    {
        return $this->state([
            'clock_in_at' => now()->subHours(3),
            'status'      => 'working',
        ]);
    }

    public function completed()
    {
        return $this->state([
            'clock_in_at'  => now()->subHours(9),
            'clock_out_at' => now(),
            'status'       => 'completed',
        ]);
    }
}