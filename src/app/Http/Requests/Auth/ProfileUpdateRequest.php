<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * HTTP 経由では使っていないので空でも問題ないが、
     * 誤用を避けるために空配列を返しておく。
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Fortify の UpdateUserProfileInformation から使うための
     * ユーザー別のルール定義。
     *
     * もともと UpdateUserProfileInformation::update() の
     * Validator::make(...) の第2引数をそのまま移植。
     */
    public static function rules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ];
    }
}