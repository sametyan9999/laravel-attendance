<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function monthly(Request $request)
    {
        $dateStr = $request->get('date');

        $baseDate = $dateStr && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)
            ? CarbonImmutable::parse($dateStr)->startOfDay()
            : CarbonImmutable::now()->timezone(config('app.timezone'))->startOfDay();

        $targetDate = $baseDate->toDateString();

        $users = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get();

        $attendances = Attendance::with('breaks')
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('user_id');

        $rows = [];

        foreach ($users as $user) {
            $att = $attendances->get($user->id);

            $clockIn = $att?->clock_in_at;
            $clockOut = $att?->clock_out_at;
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

    private function calcMinutes(Attendance $attendance): array
    {
        $breakMin = 0;

        foreach ($attendance->breaks as $b) {
            if (!$b->break_in_at || !$b->break_out_at) {
                continue;
            }

            $in = Carbon::parse($b->break_in_at);
            $out = Carbon::parse($b->break_out_at);

            if ($out->greaterThan($in)) {
                $breakMin += $out->diffInMinutes($in);
            }
        }

        $totalMin = null;

        if ($attendance->clock_in_at && $attendance->clock_out_at) {
            $ci = Carbon::parse($attendance->clock_in_at);
            $co = Carbon::parse($attendance->clock_out_at);

            $totalMin = $co->diffInMinutes($ci) - $breakMin;
            $totalMin = max($totalMin, 0);
        }

        $breakMin = $breakMin > 0 ? $breakMin : null;

        return [$breakMin, $totalMin];
    }

    public function staffIndex()
    {
        $users = User::orderBy('name')->paginate(20);

        return view('admin.staff.index', compact('users'));
    }

    public function byUser(Request $request, User $user)
    {
        $month = $request->get('month');

        $base = $month
            ? CarbonImmutable::parse($month . '-01')->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function (Attendance $att) {
                return Carbon::parse($att->work_date)->format('Y-m-d');
            });

        $rows = [];

        for ($d = $start; $d <= $end; $d = $d->addDay()) {
            $key = $d->toDateString();
            $att = $attendances->get($key);

            $breakMinutes = null;
            $totalMinutes = null;
            $clockIn = null;
            $clockOut = null;

            if ($att) {
                $clockIn = $att->clock_in_at;
                $clockOut = $att->clock_out_at;
                [$breakMinutes, $totalMinutes] = $this->calcMinutes($att);
            }

            $rows[] = [
                'date'          => $d,
                'attendance'    => $att,
                'clock_in'      => $clockIn,
                'clock_out'     => $clockOut,
                'break_minutes' => $breakMinutes,
                'total_minutes' => $totalMinutes,
            ];
        }

        return view('admin.attendance.by_user', [
            'user'  => $user,
            'rows'  => $rows,
            'month' => $base->format('Y-m'),
        ]);
    }

    public function byUserCsv(Request $request, User $user)
    {
        $month = $request->get('month');

        $base = $month
            ? CarbonImmutable::parse($month . '-01')->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function (Attendance $att) {
                return Carbon::parse($att->work_date)->format('Y-m-d');
            });

        $filename = sprintf('%s_%s_attendance.csv', $user->name, $base->format('Y-m'));

        return response()->streamDownload(function () use ($start, $end, $attendances, $user) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['氏名', '日付', '出勤', '退勤', '休憩', '合計']);

            $fmtTime = function ($v) {
                if (!$v) {
                    return '';
                }
                return Carbon::parse($v)->format('H:i');
            };

            $fmtHM = function ($min) {
                if (!is_numeric($min)) {
                    return '';
                }
                $h = intdiv((int)$min, 60);
                $m = (int)$min % 60;
                return sprintf('%d:%02d', $h, $m);
            };

            for ($d = $start; $d <= $end; $d = $d->addDay()) {
                $key = $d->format('Y-m-d');
                $att = $attendances->get($key);

                $clockIn = $att?->clock_in_at;
                $clockOut = $att?->clock_out_at;

                [$breakMin, $totalMin] = $att
                    ? $this->calcMinutes($att)
                    : [null, null];

                fputcsv($out, [
                    $user->name,
                    $d->format('Y-m-d'),
                    $fmtTime($clockIn),
                    $fmtTime($clockOut),
                    $fmtHM($breakMin),
                    $fmtHM($totalMin),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('breaks', 'user');

        return view('admin.attendance.detail', compact('attendance'));
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        // 画面の入力値（clock_in / clock_out / break1_*, break2_*）を取得
        $ci     = $request->input('clock_in', $request->input('clock_in_at'));
        $co     = $request->input('clock_out', $request->input('clock_out_at'));
        $b1_in  = $request->input('break1_in');
        $b1_out = $request->input('break1_out');
        $b2_in  = $request->input('break2_in');
        $b2_out = $request->input('break2_out');

        DB::transaction(function () use ($attendance, $request, $ci, $co, $b1_in, $b1_out, $b2_in, $b2_out) {
            // 勤怠本体を更新
            $attendance->fill([
                'clock_in_at'  => $ci ?: null,
                'clock_out_at' => $co ?: null,
                'note'         => $request->input('note'),
                'status'       => $request->input('status'),
            ])->save();

            // 既存の休憩を一旦削除
            $attendance->breaks()->delete();

            // 休憩データを再作成（1つ目 / 2つ目）
            $breaksToCreate = [];

            if (!empty($b1_in) || !empty($b1_out)) {
                $breaksToCreate[] = [
                    'break_in_at'  => $b1_in ?: null,
                    'break_out_at' => $b1_out ?: null,
                ];
            }

            if (!empty($b2_in) || !empty($b2_out)) {
                $breaksToCreate[] = [
                    'break_in_at'  => $b2_in ?: null,
                    'break_out_at' => $b2_out ?: null,
                ];
            }

            foreach ($breaksToCreate as $b) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_in_at'   => $b['break_in_at'],
                    'break_out_at'  => $b['break_out_at'],
                ]);
            }
        });

        // 修正後：個人の1ヶ月勤怠一覧へリダイレクト
        return redirect()
            ->route('admin.attendance.by_user', [
                'user'  => $attendance->user_id,
                'month' => Carbon::parse($attendance->work_date)->format('Y-m'),
            ])
            ->with('ok', true);
    }
}