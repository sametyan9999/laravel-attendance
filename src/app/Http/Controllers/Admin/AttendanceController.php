<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * PG08: 日次勤怠一覧（管理者）
     *
     * ?date=YYYY-MM-DD があればその日、無ければ「今日」を対象にする。
     * 1行 = 1ユーザー分の勤怠。
     */
    public function monthly(Request $request)
    {
        // 対象日を決定（Laravel9 なので string() ではなく get() を使用）
        $dateStr = $request->get('date');

        if ($dateStr && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            $baseDate = CarbonImmutable::parse($dateStr)->startOfDay();
        } else {
            $baseDate = CarbonImmutable::now()
                ->timezone(config('app.timezone'))
                ->startOfDay();
        }

        $targetDate = $baseDate->toDateString();

        // 一般ユーザーのみ（role = 'user' 想定）
        $users = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get();

        // 対象日の勤怠を取得（休憩もまとめて）
        $attendances = Attendance::with('breaks')
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('user_id');

        $rows = [];

        foreach ($users as $user) {
            /** @var \App\Models\Attendance|null $att */
            $att = $attendances->get($user->id);

            $clockIn      = $att?->clock_in_at;
            $clockOut     = $att?->clock_out_at;
            $breakMinutes = null;
            $totalMinutes = null;

            if ($att) {
                [$breakMinutes, $totalMinutes] = $this->calcMinutes($att);
            }

            $rows[] = [
                'user'          => $user,
                'attendance'    => $att,
                'clock_in'      => $clockIn,
                'clock_out'     => $clockOut,
                'break_minutes' => $breakMinutes,
                'total_minutes' => $totalMinutes,
            ];
        }

        return view('admin.attendance.list', [
            'date'     => $baseDate,
            'prevDate' => $baseDate->subDay(),
            'nextDate' => $baseDate->addDay(),
            'rows'     => $rows,
        ]);
    }

    /**
     * 勤怠1件分の休憩合計(分)と実働時間(分)を計算
     */
    private function calcMinutes(Attendance $attendance): array
    {
        $breakMin = 0;

        foreach ($attendance->breaks as $b) {
            if ($b->break_in_at && $b->break_out_at) {
                $in  = Carbon::parse($b->break_in_at);
                $out = Carbon::parse($b->break_out_at);

                if ($out->greaterThan($in)) {
                    $breakMin += $out->diffInMinutes($in);
                }
            }
        }

        $totalMin = null;

        if ($attendance->clock_in_at && $attendance->clock_out_at) {
            $ci = Carbon::parse($attendance->clock_in_at);
            $co = Carbon::parse($attendance->clock_out_at);

            $totalMin = $co->diffInMinutes($ci) - $breakMin;
            if ($totalMin < 0) {
                $totalMin = 0;
            }
        }

        $breakMin = $breakMin > 0 ? $breakMin : null;

        return [$breakMin, $totalMin];
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
        // Laravel9 なので string() ではなく get() を使用
        $month = $request->get('month');

        $base = $month
            ? CarbonImmutable::parse($month . '-01')
            : CarbonImmutable::now();

        $start = $base->startOfMonth()->toDateString();
        $end   = $base->endOfMonth()->toDateString();

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
            'clock_in_at.date'                     => '出勤時間が不適切な値です',
            'clock_out_at.date'                    => '退勤時間が不適切な値です',
            'clock_out_at.after_or_equal'          => '退勤時間が出勤時間より後になっている必要があります',
            'breaks.*.break_in_at.date'            => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.after_or_equal' => '休憩時間が不適切な値です',
            'note.max'                             => '備考は255文字以内で入力してください',
        ];

        $data = $request->validate([
            'clock_in_at'              => ['nullable', 'date'],
            'clock_out_at'             => ['nullable', 'date', 'after_or_equal:clock_in_at'],
            'note'                     => ['nullable', 'string', 'max:255'],
            'status'                   => ['required', 'in:off_duty,working,break,completed'],
            'breaks'                   => ['array'],
            'breaks.*.break_in_at'     => ['nullable', 'date'],
            'breaks.*.break_out_at'    => ['nullable', 'date', 'after_or_equal:breaks.*.break_in_at'],
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