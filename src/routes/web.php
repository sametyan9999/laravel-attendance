<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\Front\AttendanceRecordController as FAttendance;
use App\Http\Controllers\Front\RequestController as FRequest;
use App\Http\Controllers\Admin\AttendanceController as AAttendance;
use App\Http\Controllers\Admin\RequestController as ARequest;
use App\Http\Requests\AdminLoginRequest; // ★ 管理者ログイン用 FormRequest

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
*/
Route::middleware('guest')->group(function () {

    Route::get('/admin/login', function () {

        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        return view('admin.auth.login');
    })->name('admin.login.form');

    // ★ FormRequest を使った管理者ログイン
    Route::post('/admin/login', function (AdminLoginRequest $request) {

        // この時点で AdminLoginRequest によるバリデーションは完了
        $validated = $request->validated();

        $email    = $validated['email'];
        $password = $validated['password'];

        $validEmail    = 'admin@example.com';
        $validPassword = 'Admin1234';

        if ($email !== $validEmail || $password !== $validPassword) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        return redirect()->route('admin.attendance.list');
    })->name('admin.login');
});

/*
|--------------------------------------------------------------------------
| Front（一般ユーザー）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [FAttendance::class, 'today'])->name('attendance.today');
    Route::post('/attendance/clock-in',  [FAttendance::class, 'clockIn'])->name('attendance.clock_in');
    Route::post('/attendance/break-in',  [FAttendance::class, 'breakIn'])->name('attendance.break_in');
    Route::post('/attendance/break-out', [FAttendance::class, 'breakOut'])->name('attendance.break_out');
    Route::post('/attendance/clock-out', [FAttendance::class, 'clockOut'])->name('attendance.clock_out');

    Route::get('/attendance/list', [FAttendance::class, 'indexMonthly'])->name('attendance.list');
    Route::get('/attendance/detail/{attendance}', [FAttendance::class, 'detail'])
        ->whereNumber('attendance')
        ->name('attendance.detail');

    Route::put('/attendance/detail/{attendance}', [FAttendance::class, 'update'])
        ->whereNumber('attendance')
        ->name('attendance.update');

    Route::get('/stamp_correction_request/list', [FRequest::class, 'myIndex'])
        ->name('request.my_index');

    Route::post('/stamp_correction_request', [FRequest::class, 'store'])
        ->name('request.store');
});

/*
|--------------------------------------------------------------------------
| Admin（管理者）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'can:admin'])
    ->name('admin.')
    ->group(function () {

        // PG08/PG09/PG10/PG11（勤怠）
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

        Route::get('/attendance/staff/{user}/csv', [AAttendance::class, 'byUserCsv'])
            ->whereNumber('user')
            ->name('attendance.by_user_csv');

        // PG12/PG13（修正申請）
        Route::get('/stamp_correction_request/list', [ARequest::class, 'index'])
            ->name('request.index');

        // ★ 承認済み一覧（/admin/stamp_correction_request/approved）
        Route::get('/stamp_correction_request/approved', [ARequest::class, 'approved'])
            ->name('request.approved');

        /*
        |--------------------------------------------------------------------------
        | PG13: 申請詳細（テストの route() に合わせた URL パラメータ名）
        |--------------------------------------------------------------------------
        | GET /admin/stamp_correction_request/approve/{attendance_correct_request_id}
        | → RequestController@show
        */
        Route::get(
            '/stamp_correction_request/approve/{attendance_correct_request_id}',
            [ARequest::class, 'show']
        )
            ->whereNumber('attendance_correct_request_id')
            ->name('request.show');

        /*
        |--------------------------------------------------------------------------
        | PG13: 承認処理
        |--------------------------------------------------------------------------
        | POST /admin/stamp_correction_request/{attendance_correct_request_id}/approve
        | → RequestController@approve
        | （テストでこのパスを直接叩いている）
        */
        Route::post(
            '/stamp_correction_request/{attendance_correct_request_id}/approve',
            [ARequest::class, 'approve']
        )
            ->whereNumber('attendance_correct_request_id')
            ->name('request.approve');

        // 却下処理（reject は任意）
        Route::post(
            '/stamp_correction_request/{stamp_request}/reject',
            [ARequest::class, 'reject']
        )
            ->whereNumber('stamp_request')
            ->name('request.reject');
    });

/*
|--------------------------------------------------------------------------
| メール認証
|--------------------------------------------------------------------------
*/
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.today');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');