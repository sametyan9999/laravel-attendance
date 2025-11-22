<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;

class AdminLoginController extends Controller
{
    private const VALID_EMAIL = 'admin@example.com';
    private const VALID_PASSWORD = 'Admin1234';

    public function login(AdminLoginRequest $request)
    {
        $email = $request->email;
        $password = $request->password;

        if ($email !== self::VALID_EMAIL || $password !== self::VALID_PASSWORD) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        return redirect()->route('admin.attendance.list');
    }
}