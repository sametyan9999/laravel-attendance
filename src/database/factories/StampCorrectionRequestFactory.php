<?php

namespace Database\Factories;

use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    protected $model = StampCorrectionRequest::class;

    public function definition()
    {
        return [
            'attendance_id'           => Attendance::factory(),
            'requested_clock_in_at'   => now()->subHours(8),
            'requested_clock_out_at'  => now(),
            'requested_break_minutes' => 60,
            'requested_note'          => 'テスト用の申請理由',
            'status'                  => 'pending',
            'requested_by'            => User::factory(),
            'approved_by'             => null,
            'approved_at'             => null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ];
    }

    public function approved()
    {
        return $this->state([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);
    }
}