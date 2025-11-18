<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 却下理由（任意）のバリデーション
     * もともと Admin\RequestController@reject の $http->validate() と同じ
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}