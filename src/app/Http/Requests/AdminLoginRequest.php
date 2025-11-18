<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    /**
     * このリクエストを誰でも使えるようにする
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * FN015: 使用技術 formrequest
     * FN016: 「未入力の場合」のチェックをここで行う
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * エラーメッセージ
     *
     * FN016 に書かれている文言を一字一句そのまま使う
     */
    public function messages(): array
    {
        return [
            // 1. 未入力の場合
            //    1. メールアドレスを入力してください
            'email.required'    => 'メールアドレスを入力してください',

            //    2. パスワードを入力してください
            'password.required' => 'パスワードを入力してください',
        ];
    }
}