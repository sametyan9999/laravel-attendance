<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 管理者承認時の任意メモ用
     * もともと Admin\RequestController@approve の $http->validate() と同じ
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}