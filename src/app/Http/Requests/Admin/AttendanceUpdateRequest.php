<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * 認可はコントローラ側で実施していないので、ここでは true を返す
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 1次バリデーション（形式チェック）
     */
    public function rules(): array
    {
        return [
            'clock_in_at'   => ['nullable', 'date_format:H:i'],
            'clock_out_at'  => ['nullable', 'date_format:H:i'],
            'note'          => ['required', 'string', 'max:255'],
            'status'        => ['required', 'in:off_duty,working,break,completed'],

            'breaks'                => ['array'],
            'breaks.*.break_in_at'  => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * カスタムメッセージ
     */
    public function messages(): array
    {
        return [
            'clock_in_at.date_format'           => '出勤時間が不適切な値です',
            'clock_out_at.date_format'          => '退勤時間が不適切な値です',
            'breaks.*.break_in_at.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',
            'note.required'                     => '備考を記入してください',
            'note.max'                          => '備考は255文字以内で入力してください',
        ];
    }

    /**
     * 2次バリデーション（前後関係のチェック）
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ci = $this->input('clock_in_at', $this->input('clock_in'));
            $co = $this->input('clock_out_at', $this->input('clock_out'));

            // ①「出勤時間が退勤時間より後」 → 「出勤時間もしくは退勤時間が不適切な値です」
            if ($ci !== null && $co !== null && $co < $ci) {
                $msg = '出勤時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('clock_out', $msg);
                $validator->errors()->add('clock_out_at', $msg);
            }

            // ②「休憩開始時間が退勤時間より後」 → 「休憩時間が不適切な値です」
            $break1In  = $this->input('break1_in');
            if ($break1In !== null && $co !== null && $break1In > $co) {
                $msg = '休憩時間が不適切な値です';
                $validator->errors()->add('break1_in', $msg);
                $validator->errors()->add('breaks.0.break_in_at', $msg);
            }

            // ③「休憩終了時間が退勤時間より後」 → 「休憩時間もしくは退勤時間が不適切な値です」 ←★ここを修正済み
            $break1Out = $this->input('break1_out');
            if ($break1Out !== null && $co !== null && $break1Out > $co) {
                $msg = '休憩時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('break1_out', $msg);
                $validator->errors()->add('breaks.0.break_out_at', $msg);
            }

            // breaks配列でもチェック
            foreach ((array) $this->input('breaks', []) as $idx => $b) {
                $bi = $b['break_in_at'] ?? null;
                $bo = $b['break_out_at'] ?? null;

                if ($bi !== null && $bo !== null && $bo < $bi) {
                    $msg = $idx === 0
                        ? '休憩時間が不適切な値です'
                        : '休憩時間が不適切な値です';

                    $validator->errors()->add("breaks.$idx.break_out_at", $msg);
                }
            }
        });
    }
}