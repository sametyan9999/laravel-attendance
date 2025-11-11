<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * PG08: 全体 月次一覧
     */
    public function monthly(Request $request)
    {
        $month = $request->string('month')->toString();
        $base = $month ? CarbonImmutable::parse($month . '-01') : CarbonImmutable::now();
        $start = $base->startOfMonth()->toDateString();
        $end = $base->endOfMonth()->toDateString();

        $rows = Attendance::with('user')
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('user_id')
            ->orderBy('work_date')
            ->paginate(50);

        return view('admin.attendance.monthly', [
            'rows'  => $rows,
            'month' => $base->format('Y-m'),
        ]);
    }

    /**
     * PG10: スタッフ一覧
     */
    public function staffIndex()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('admin.staff.index', compact('users'));
    }

    /**
     * PG11: スタッフ別 月次一覧
     */
    public function byUser(Request $request, User $user)
    {
        $month = $request->string('month')->toString();
        $base = $month ? CarbonImmutable::parse($month . '-01') : CarbonImmutable::now();
        $start = $base->startOfMonth()->toDateString();
        $end = $base->endOfMonth()->toDateString();

        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get();

        return view('admin.attendance.by_user', [
            'user'  => $user,
            'rows'  => $rows,
            'month' => $base->format('Y-m'),
        ]);
    }

    /**
     * PG09: 日次詳細（管理者編集）
     */
    public function show(Attendance $attendance)
    {
        $attendance->load('breaks', 'user');
        return view('admin.attendance.detail', compact('attendance'));
    }

    /**
     * PG09: 日次の直接修正
     */
    public function update(Request $request, Attendance $attendance)
    {
        $messages = [
            'clock_in_at.date'                   => '出勤時間が不適切な値です',
            'clock_out_at.date'                  => '退勤時間が不適切な値です',
            'clock_out_at.after_or_equal'        => '退勤時間が出勤時間より後になっている必要があります',
            'breaks.*.break_in_at.date'          => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.after_or_equal'=> '休憩時間が不適切な値です',
            'note.max'                           => '備考は255文字以内で入力してください',
        ];

        $data = $request->validate([
            'clock_in_at'  => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date', 'after_or_equal:clock_in_at'],
            'note'         => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'in:off_duty,working,break,completed'],
            'breaks'       => ['array'],
            'breaks.*.break_in_at'  => ['nullable', 'date'],
            'breaks.*.break_out_at' => ['nullable', 'date', 'after_or_equal:breaks.*.break_in_at'],
        ], $messages);

        DB::transaction(function () use ($attendance, $data) {
            $attendance->fill([
                'clock_in_at'  => $data['clock_in_at']  ?? null,
                'clock_out_at' => $data['clock_out_at'] ?? null,
                'note'         => $data['note']         ?? null,
                'status'       => $data['status'],
            ])->save();

            // 休憩は単純化のため全削除→再作成
            $attendance->breaks()->delete();
            foreach ($data['breaks'] ?? [] as $b) {
                if (!empty($b['break_in_at']) || !empty($b['break_out_at'])) {
                    AttendanceBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_in_at'   => $b['break_in_at']  ?? null,
                        'break_out_at'  => $b['break_out_at'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('ok', true);
    }
}