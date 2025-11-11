<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * 自分の勤怠のみ閲覧を許可
     * 管理者は全件閲覧可能
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        return $attendance->user_id === $user->id;
    }
}