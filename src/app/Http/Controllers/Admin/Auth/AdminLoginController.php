<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;

class AdminLoginController extends Controller
{
    public function login(AdminLoginRequest $request)
    {
        // 固定の管理者アカウント
        $validEmail    = 'admin@example.com';
        $validPassword = 'Admin1234';

        // 入力値
        $email    = $request->email;
        $password = $request->password;

        // 認証チェック（要件のエラーメッセージ）
        if ($email !== $validEmail || $password !== $validPassword) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        // ログイン成功 → 管理者勤怠一覧へ
        return redirect()->route('admin.attendance.list');
    }
}