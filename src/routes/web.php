<?php

use Illuminate\Support\Facades\Route;
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
| コントローラを作らずに、ビューを返すだけのルートにする
*/
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        // すでに管理者でログイン済みなら勤怠一覧へ
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        // 管理者ログイン用 Blade テンプレート
        return view('admin.auth.login');
    })->name('admin.login');
});

/*
|--------------------------------------------------------------------------
| Front (一般ユーザー)
|--------------------------------------------------------------------------
| Fortify が /login /register などの認証系ルートを提供します。
| ここではアプリ固有の画面のみ定義します。
*/
Route::middleware(['auth'])->group(function () {
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