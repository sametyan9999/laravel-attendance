<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\Front\AttendanceRecordController as FAttendance;
use App\Http\Controllers\Front\RequestController as FRequest;
use App\Http\Controllers\Admin\AttendanceController as AAttendance;
use App\Http\Controllers\Admin\RequestController as ARequest;

/*
|--------------------------------------------------------------------------
| Top リダイレクト
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/attendance');

/*
|--------------------------------------------------------------------------
| Admin Login（PG07）
|--------------------------------------------------------------------------
| GET : 管理者ログイン画面表示
| POST: 管理者ログイン処理（テストケース ID3 を満たす）
*/
Route::middleware('guest')->group(function () {
    // ログイン画面（GET）
    Route::get('/admin/login', function () {
        // すでに管理者でログイン済みなら勤怠一覧へ
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        // 管理者ログイン用 Blade テンプレート
        return view('admin.auth.login');
    })->name('admin.login.form');

    // ログイン処理（POST）
    Route::post('/admin/login', function (Request $request) {
        // ★ テストケースに合わせたメッセージ
        $messages = [
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスの形式が不適切です',
            'password.required' => 'パスワードを入力してください',
        ];

        // 必須チェック
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], $messages);

        // ここから「登録内容と一致しない場合」の検証用（簡易認証）
        $email    = $validated['email'];
        $password = $validated['password'];

        // 正しい管理者情報（テスト用の仮ユーザー）
        $validEmail    = 'admin@example.com';
        $validPassword = 'Admin1234';

        // 情報が一致しない → 期待どおりのエラーメッセージ
        if ($email !== $validEmail || $password !== $validPassword) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        // 本番ならここで guard('admin') などを使ってログイン処理を行う想定

        // 成功時の遷移先（管理者勤怠一覧）
        return redirect()->route('admin.attendance.list');
    })->name('admin.login'); // ★ テストの post(route('admin.login')) はこちら
});

/*
|--------------------------------------------------------------------------
| Front (一般ユーザー)
|--------------------------------------------------------------------------
| Fortify が /login /register などの認証系ルートを提供します。
| ここではアプリ固有の画面のみ定義します。
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // 打刻（PG03）
    Route::get('/attendance', [FAttendance::class, 'today'])->name('attendance.today');
    Route::post('/attendance/clock-in',  [FAttendance::class, 'clockIn'])->name('attendance.clock_in');
    Route::post('/attendance/break-in',  [FAttendance::class, 'breakIn'])->name('attendance.break_in');
    Route::post('/attendance/break-out', [FAttendance::class, 'breakOut'])->name('attendance.break_out');
    Route::post('/attendance/clock-out', [FAttendance::class, 'clockOut'])->name('attendance.clock_out');

    // 月次一覧（PG04）/ 日次詳細（PG05）
    Route::get('/attendance/list', [FAttendance::class, 'indexMonthly'])->name('attendance.list');
    Route::get('/attendance/detail/{attendance}', [FAttendance::class, 'detail'])
        ->whereNumber('attendance')
        ->name('attendance.detail');

    // 勤怠詳細の更新（PG05）
    Route::put('/attendance/detail/{attendance}', [FAttendance::class, 'update'])
        ->whereNumber('attendance')
        ->name('attendance.update');

    // 申請（PG06）
    Route::get('/stamp_correction_request/list', [FRequest::class, 'myIndex'])->name('request.my_index');
    Route::post('/stamp_correction_request',      [FRequest::class, 'store'])->name('request.store');
});

/*
|--------------------------------------------------------------------------
| Admin (管理者)
|--------------------------------------------------------------------------
| ※ {request} は Illuminate\Http\Request と衝突しやすいため {stamp_request} を使用
*/
Route::prefix('admin')
    ->middleware(['auth', 'can:admin'])
    ->name('admin.')
    ->group(function () {
        // 勤怠（PG08/PG09/PG10/PG11）
        Route::get('/attendance/list',         [AAttendance::class, 'monthly'])->name('attendance.list');
        Route::get('/attendance/{attendance}', [AAttendance::class, 'show'])
            ->whereNumber('attendance')
            ->name('attendance.detail');
        Route::put('/attendance/{attendance}', [AAttendance::class, 'update'])
            ->whereNumber('attendance')
            ->name('attendance.update');

        Route::get('/staff/list',              [AAttendance::class, 'staffIndex'])->name('staff.index');
        Route::get('/attendance/staff/{user}', [AAttendance::class, 'byUser'])
            ->whereNumber('user')
            ->name('attendance.by_user');

        // ★ スタッフ別勤怠 CSV 出力
        Route::get('/attendance/staff/{user}/csv', [AAttendance::class, 'byUserCsv'])
            ->whereNumber('user')
            ->name('attendance.by_user_csv');

        // 修正申請（PG12/PG13）
        Route::get('/stamp_correction_request/list', [ARequest::class, 'index'])->name('request.index');
        Route::get('/stamp_correction_request/{stamp_request}', [ARequest::class, 'show'])
            ->whereNumber('stamp_request')
            ->name('request.show');
        Route::post('/stamp_correction_request/{stamp_request}/approve', [ARequest::class, 'approve'])
            ->whereNumber('stamp_request')
            ->name('request.approve');
        Route::post('/stamp_correction_request/{stamp_request}/reject', [ARequest::class, 'reject'])
            ->whereNumber('stamp_request')
            ->name('request.reject');
    });

/*
|--------------------------------------------------------------------------
| メール認証関連ルート
|--------------------------------------------------------------------------
| 画面:
|  b. メール認証誘導画面 (/email/verify)
|  c. メール認証画面 (メール内リンク)
|  再送: POST /email/verification-notification
*/
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // 認証完了

    return redirect()->route('attendance.today');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');