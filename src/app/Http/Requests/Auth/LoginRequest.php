<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Fortify のログイン処理から内部的に使うだけなので true でOK
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 一般ユーザーのログインフォーム用バリデーション
     *
     * FN008:
     *  - 未入力の場合
     *      1. メールアドレスを入力してください
     *      2. パスワードを入力してください
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * 日本語エラーメッセージ
     * （機能要件シート FN008 に合わせた文言）
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスの形式が不適切です',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}