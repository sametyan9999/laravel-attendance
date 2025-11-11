<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\User;
use App\Policies\AttendancePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Attendance::class => AttendancePolicy::class,
        // 他のモデルのポリシーを追加する場合はここに追記
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // 管理者判定（routes で 'can:admin' を使う）
        Gate::define('admin', function (User $user): bool {
            return $user->role === 'admin';
        });
    }
}