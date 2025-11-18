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
     * もともと AttendanceController@update の $rules をそのまま移植
     */
    public function rules(): array
    {
        return [
            // 出勤・退勤 … time 型入力なので H:i 形式でチェック
            'clock_in_at'   => ['nullable', 'date_format:H:i'],
            'clock_out_at'  => ['nullable', 'date_format:H:i'],

            // 備考（必須）
            'note' => ['required', 'string', 'max:255'],

            // ステータス
            'status' => ['required', 'in:off_duty,working,break,completed'],

            // 休憩（画面側は breaks[0][break_in_at] など）
            'breaks'                => ['array'],
            'breaks.*.break_in_at'  => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * カスタムメッセージ
     * もともと AttendanceController@update の $messages をそのまま移植
     */
    public function messages(): array
    {
        return [
            // 形式エラー
            'clock_in_at.date_format'           => '出勤時刻が不適切な値です',
            'clock_out_at.date_format'          => '退勤時刻が不適切な値です',
            'breaks.*.break_in_at.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',

            // 備考
            'note.required'                     => '備考を記入してください',
            'note.max'                          => '備考は255文字以内で入力してください',
        ];
    }

    /**
     * 2次バリデーション（前後関係のチェック）
     * もともと AttendanceController@update の $validator->after(...) をそのまま移植
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // テストは clock_in / clock_out を送ってくるので、そちらも拾う
            $ci = $this->input('clock_in_at', $this->input('clock_in'));
            $co = $this->input('clock_out_at', $this->input('clock_out'));

            // 1) 出勤時間が退勤時間より後 → 「退勤時間が出勤時間より前になっています。」
            if ($ci !== null && $co !== null && $co < $ci) {
                $msg = '退勤時間が出勤時間より前になっています。';

                // テスト用フィールド名
                $validator->errors()->add('clock_out', $msg);
                // 画面用フィールド名
                $validator->errors()->add('clock_out_at', $msg);
            }

            // 休憩（テスト用パラメータ）
            $break1In  = $this->input('break1_in');
            $break1Out = $this->input('break1_out');

            // 2) 休憩開始時間が退勤時間より後 → 「休憩時間が勤務時間の範囲外です。」
            if ($break1In !== null && $co !== null && $break1In > $co) {
                $msg = '休憩時間が勤務時間の範囲外です。';

                // テスト用フィールド名
                $validator->errors()->add('break1_in', $msg);
                // 画面用フィールド名（1行目の休憩開始）
                $validator->errors()->add('breaks.0.break_in_at', $msg);
            }

            // 3) 休憩終了時間が退勤時間より後 → 「休憩1の終了が開始より前になっています。」
            if ($break1Out !== null && $co !== null && $break1Out > $co) {
                $msg = '休憩1の終了が開始より前になっています。';

                // テスト用フィールド名
                $validator->errors()->add('break1_out', $msg);
                // 画面用フィールド名（1行目の休憩終了）
                $validator->errors()->add('breaks.0.break_out_at', $msg);
            }

            // 画面から送られてくる breaks 配列側についても前後関係チェック
            foreach ((array) $this->input('breaks', []) as $idx => $b) {
                $bi = $b['break_in_at'] ?? null;
                $bo = $b['break_out_at'] ?? null;

                if ($bi !== null && $bo !== null && $bo < $bi) {
                    // 1行目だけは上と同じメッセージにしておく
                    $msg = $idx === 0
                        ? '休憩1の終了が開始より前になっています。'
                        : '休憩時間が不適切な値です';

                    $validator->errors()->add("breaks.$idx.break_out_at", $msg);
                }
            }
        });
    }
}