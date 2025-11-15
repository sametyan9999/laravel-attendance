<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // API など JSON を期待する場合はリダイレクトしない
        if ($request->expectsJson()) {
            return null;
        }

        // ★ admin配下のURLに未認証でアクセスした場合は「管理者ログイン」へ
        if ($request->is('admin/*')) {
            return route('admin.login');
        }

        // ★ それ以外は通常のログイン画面へ
        return route('login');
    }
}