<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\Front\AttendanceRecordController as FAttendance;
use App\Http\Controllers\Front\RequestController as FRequest;
use App\Http\Controllers\Admin\AttendanceController as AAttendance;
use App\Http\Controllers\Admin\RequestController as ARequest;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;

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

    Route::post('/admin/login', function (AdminLoginRequest $request) {
        $validated = $request->validated();

        $email    = $validated['email'];
        $password = $validated['password'];

        $user = User::where('email', $email)->first();

        if (
            !$user ||
            !Hash::check($password, $user->password) ||
            $user->role !== 'admin'
        ) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

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

        Route::get('/attendance/list', [AAttendance::class, 'monthly'])->name('attendance.list');
        Route::get('/attendance/{attendance}', [AAttendance::class, 'show'])
            ->whereNumber('attendance')
            ->name('attendance.detail');
        Route::put('/attendance/{attendance}', [AAttendance::class, 'update'])
            ->whereNumber('attendance')
            ->name('attendance.update');

        Route::get('/staff/list', [AAttendance::class, 'staffIndex'])->name('staff.index');
        Route::get('/attendance/staff/{user}', [AAttendance::class, 'byUser'])
            ->whereNumber('user')
            ->name('attendance.by_user');

        Route::get('/attendance/staff/{user}/csv', [AAttendance::class, 'byUserCsv'])
            ->whereNumber('user')
            ->name('attendance.by_user_csv');

        Route::get('/stamp_correction_request/list', [ARequest::class, 'index'])
            ->name('request.index');

        Route::get('/stamp_correction_request/approved', [ARequest::class, 'approved'])
            ->name('request.approved');

        Route::get(
            '/stamp_correction_request/approve/{attendance_correct_request_id}',
            [ARequest::class, 'show']
        )
            ->whereNumber('attendance_correct_request_id')
            ->name('request.show');

        Route::post(
            '/stamp_correction_request/{attendance_correct_request_id}/approve',
            [ARequest::class, 'approve']
        )
            ->whereNumber('attendance_correct_request_id')
            ->name('request.approve');

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