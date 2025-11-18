<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ★ ログイン後レスポンスをカスタム LoginResponse に差し替え
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // あなたの Blade を Fortify の画面として利用
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));

        /*
        |--------------------------------------------------------------------------
        | 一般ユーザー ログイン認証（Fortify + FormRequest）
        |--------------------------------------------------------------------------
        | - LoginRequest(FormRequest) のルール／メッセージでバリデーション
        | - 認証失敗時は「ログイン情報が登録されていません」を返す
        */
        Fortify::authenticateUsing(function (Request $request) {
            /** @var LoginRequest $form */
            $form = app(LoginRequest::class);

            // FormRequest の rules/messages をそのまま利用
            $validator = Validator::make(
                $request->all(),
                $form->rules(),
                $form->messages()
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // 入力情報が誤っている場合（FN008 2.）
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        });

        // 標準機能のハンドラ登録
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ログイン試行回数制限（5回/分）
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}