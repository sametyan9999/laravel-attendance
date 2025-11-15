<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * ログイン成功後のレスポンス
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // ★ 管理者なら「必ず」管理者勤怠一覧へ
        if ($user && $user->role === 'admin') {
            // intended は使わず、URL を固定する
            return redirect('/admin/attendance/list');

            // 日付付きにしたいなら、例えばこう：
            // $today = now()->timezone(config('app.timezone'))->toDateString();
            // return redirect()->route('admin.attendance.list', ['date' => $today]);
        }

        // ★ 一般ユーザーは intended を尊重しつつ、デフォルトは /attendance
        return redirect()->intended('/attendance');
        // route 名で書くなら：
        // return redirect()->intended(route('attendance.today'));
    }
}