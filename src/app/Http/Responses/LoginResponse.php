<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user && $user->role === 'admin') {
            return redirect('/admin/attendance/list');
        }

        return redirect()->intended('/attendance');
    }
}