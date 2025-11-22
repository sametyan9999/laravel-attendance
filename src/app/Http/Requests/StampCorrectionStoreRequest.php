<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StampCorrectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],
            'requested_clock_in_at' => ['nullable', 'date'],
            'requested_clock_out_at' => ['nullable', 'date', 'after_or_equal:requested_clock_in_at'],
            'requested_break_minutes' => ['nullable', 'integer', 'min:0'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => '対象勤怠が不明です。',
            'requested_clock_in_at.date' => '出勤時刻が不適切な値です',
            'requested_clock_out_at.date' => '退勤時刻が不適切な値です',
            'requested_clock_out_at.after_or_equal'
                => '退勤時間は出勤時間以降に設定してください',
            'requested_break_minutes.integer' => '休憩時間が不適切な値です',
            'requested_break_minutes.min' => '休憩時間が不適切な値です',
            'note.required' => '備考を記入してください',
        ];
    }
}