<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in_at' => ['nullable', 'date_format:H:i'],
            'clock_out_at' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:off_duty,working,break,completed'],
            'breaks' => ['array'],
            'breaks.*.break_in_at' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in_at.date_format' => '出勤時間が不適切な値です',
            'clock_out_at.date_format' => '退勤時間が不適切な値です',
            'breaks.*.break_in_at.date_format' => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // フォームは clock_in / clock_out を送ってくるので、そちらを優先して見る
            $ci = $this->input('clock_in', $this->input('clock_in_at'));
            $co = $this->input('clock_out', $this->input('clock_out_at'));

            if ($ci !== null && $co !== null && $co < $ci) {
                $msg = '出勤時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('clock_out', $msg);
                $validator->errors()->add('clock_out_at', $msg);
            }

            $break1In = $this->input('break1_in');
            if ($break1In !== null && $co !== null && $break1In > $co) {
                $msg = '休憩時間が不適切な値です';
                $validator->errors()->add('break1_in', $msg);
                $validator->errors()->add('breaks.0.break_in_at', $msg);
            }

            $break1Out = $this->input('break1_out');
            if ($break1Out !== null && $co !== null && $break1Out > $co) {
                $msg = '休憩時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('break1_out', $msg);
                $validator->errors()->add('breaks.0.break_out_at', $msg);
            }

            foreach ((array) $this->input('breaks', []) as $idx => $b) {
                $bi = $b['break_in_at'] ?? null;
                $bo = $b['break_out_at'] ?? null;

                if ($bi !== null && $bo !== null && $bo < $bi) {
                    $validator->errors()->add("breaks.$idx.break_out_at", '休憩時間が不適切な値です');
                }
            }
        });
    }
}