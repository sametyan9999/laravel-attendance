<?php

namespace App\Http\Requests\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            // パスワード：必須・8文字以上・確認一致（PasswordValidationRules 側で定義）
            'password' => $this->passwordRules(),

            // 確認用パスワード：必須・8文字以上
            'password_confirmation' => [
                'required',
                'string',
                'min:8',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.string'   => 'お名前を正しく入力してください',
            'name.max'      => '名前は255文字以内で入力してください',

            'email.required' => 'メールアドレスを入力してください',
            'email.email'    => 'メールアドレスの形式が不適切です',
            'email.max'      => 'メールアドレスは255文字以内で入力してください',
            'email.unique'   => 'このメールアドレスは既に登録されています',

            'password.required'  => 'パスワードを入力してください',
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードが一致しません',

            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.min'      => '確認用パスワードは8文字以上で入力してください',
        ];
    }
}