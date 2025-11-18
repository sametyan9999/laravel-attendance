<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * このリクエストを許可するか
     * 認可はコントローラ側でやっているので true でOK
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 勤怠修正フォームのバリデーションルール
     */
    public function rules(): array
    {
        return [
            'clock_in'   => ['nullable', 'date_format:H:i'],
            'clock_out'  => ['nullable', 'date_format:H:i'],
            'break1_in'  => ['nullable', 'date_format:H:i'],
            'break1_out' => ['nullable', 'date_format:H:i'],
            'break2_in'  => ['nullable', 'date_format:H:i'],
            'break2_out' => ['nullable', 'date_format:H:i'],
            'note'       => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * カスタムメッセージ
     * （元々コントローラに書いていたものを移動）
     */
    public function messages(): array
    {
        return [
            'clock_in.date_format'   => '出勤時刻は「HH:MM」形式で入力してください。',
            'clock_out.date_format'  => '退勤時刻は「HH:MM」形式で入力してください。',
            'break1_in.date_format'  => '休憩1の開始時刻は「HH:MM」形式で入力してください。',
            'break1_out.date_format' => '休憩1の終了時刻は「HH:MM」形式で入力してください。',
            'break2_in.date_format'  => '休憩2の開始時刻は「HH:MM」形式で入力してください。',
            'break2_out.date_format' => '休憩2の終了時刻は「HH:MM」形式で入力してください。',
            'note.required'          => '備考を記入してください',
        ];
    }
}