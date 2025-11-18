<?php

namespace App\Http\Requests\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        // Fortify から内部的に使うだけなので true
        return true;
    }

    /**
     * 会員登録フォームのバリデーション
     * もともと CreateNewUser::create() の Validator::make(...) を移植
     */
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
            // PasswordValidationRules::passwordRules() をそのまま利用
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * 日本語のエラーメッセージ
     * もともと CreateNewUser::create() の第3引数を移植
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'お名前を入力してください',
            'name.string'        => 'お名前を正しく入力してください',
            'name.max'           => '名前は255文字以内で入力してください',

            'email.required'     => 'メールアドレスを入力してください',
            'email.email'        => 'メールアドレスの形式が不適切です',
            'email.max'          => 'メールアドレスは255文字以内で入力してください',
            'email.unique'       => 'このメールアドレスは既に登録されています',

            'password.required'  => 'パスワードを入力してください',
            // PasswordValidationRules 側で min:8 を使っている前提
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードが一致しません',
        ];
    }
}