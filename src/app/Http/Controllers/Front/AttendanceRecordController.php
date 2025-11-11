<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    /**
     * PG03: 本日の打刻画面
     */
    public function today(Request $request)
    {
        $user = Auth::user();
        $today = CarbonImmutable::now()->timezone(config('app.timezone'))->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['status' => 'off_duty']
        )->load('breaks');

        return view('attendance.today', [
            'attendance' => $attendance,
            'now'        => CarbonImmutable::now(),
        ]);
    }

    /**
     * 出勤
     */
    public function clockIn(Request $request)
    {
        return $this->transition(function (Attendance $attendance, CarbonImmutable $now) {
            if ($attendance->status !== 'off_duty') {
                return back()->withErrors(['clock_in' => '既に出勤済みです。']);
            }
            $attendance->clock_in_at = $now;
            $attendance->status = 'working';
            $attendance->save();
        });
    }

    /**
     * 休憩入
     */
    public function breakIn(Request $request)
    {
        return $this->transition(function (Attendance $attendance, CarbonImmutable $now) {
            if ($attendance->status !== 'working') {
                return back()->withErrors(['break_in' => '休憩開始は勤務中のみ可能です。']);
            }
            DB::transaction(function () use ($attendance, $now) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_in_at'   => $now,
                ]);
                $attendance->status = 'break';
                $attendance->save();
            });
        });
    }

    /**
     * 休憩戻
     */
    public function breakOut(Request $request)
    {
        return $this->transition(function (Attendance $attendance, CarbonImmutable $now) {
            if ($attendance->status !== 'break') {
                return back()->withErrors(['break_out' => '休憩終了は休憩中のみ可能です。']);
            }
            DB::transaction(function () use ($attendance, $now) {
                $last = $attendance->breaks()->latest('id')->first();
                if (!$last || $last->break_out_at) {
                    abort(422, '休憩の開始が見つかりません。');
                }
                $last->break_out_at = $now;
                $last->save();

                $attendance->status = 'working';
                $attendance->save();
            });
        });
    }

    /**
     * 退勤（休憩中なら直前休憩を自動クローズ）
     */
    public function clockOut(Request $request)
    {
        return $this->transition(function (Attendance $attendance, CarbonImmutable $now) {
            if (!in_array($attendance->status, ['working', 'break'], true)) {
                return back()->withErrors(['clock_out' => '退勤できる状態ではありません。']);
            }
            DB::transaction(function () use ($attendance, $now) {
                if ($attendance->status === 'break') {
                    $last = $attendance->breaks()->latest('id')->first();
                    if ($last && !$last->break_out_at) {
                        $last->break_out_at = $now;
                        $last->save();
                    }
                }
                $attendance->clock_out_at = $now;
                $attendance->status = 'completed';
                $attendance->save();
            });
        });
    }

    /**
     * PG04: 月次一覧（?month=YYYY-MM）
     * 参考画像に合わせた行データを構築
     */
    public function indexMonthly(Request $request)
    {
        $user = Auth::user();

        $base = $request->filled('month')
            ? CarbonImmutable::parse($request->string('month')->toString() . '-01')
            : CarbonImmutable::now();

        $month = $base->format('Y-m');
        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();

        // 今月分の勤怠をロード（休憩も同時）
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        // 日付ごとの行配列を作成
        $days = [];
        for ($d = $start; $d <= $end; $d = $d->addDay()) {
            $att = $attendances->get($d->toDateString());

            // 休憩合計（分）
            if ($att) {
                $breakMin = 0;
                foreach ($att->breaks as $b) {
                    if ($b->break_in_at && $b->break_out_at) {
                        $breakMin += $b->break_out_at->diffInMinutes($b->break_in_at);
                    }
                }
                $att->break_hm = $breakMin > 0 ? $this->minToHM($breakMin) : '—';

                // 勤務合計 = 退勤-出勤-休憩
                if ($att->clock_in_at && $att->clock_out_at) {
                    $workMin = $att->clock_out_at->diffInMinutes($att->clock_in_at) - $breakMin;
                    $att->work_hm = $workMin >= 0 ? $this->minToHM($workMin) : '—';
                } else {
                    $att->work_hm = '—';
                }
            }

            $days[] = [
                'date' => $d,
                'attendance' => $att,
            ];
        }

        return view('attendance.list', [
            'days'      => $days,
            'month'     => $month,
            'prevMonth' => $base->subMonth()->format('Y-m'),
            'nextMonth' => $base->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * PG05: 日次詳細
     */
    public function detail(Attendance $attendance)
    {
        $attendance->load('breaks', 'user');
        $this->authorize('view', $attendance); // 自分の勤怠のみ
        return view('attendance.detail', compact('attendance'));
    }

    /**
     * 状態遷移の共通ラッパ
     */
    private function transition(\Closure $handler)
    {
        $user = Auth::user();
        $today = CarbonImmutable::now()->timezone(config('app.timezone'))->toDateString();
        $now = CarbonImmutable::now();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['status' => 'off_duty']
        );

        try {
            $result = $handler($attendance, $now);
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['system' => $e->getMessage()])->withInput();
        }
        return back()->with('ok', true);
    }

    /** 分→H:MM 表記 */
    private function minToHM(int $min): string
    {
        $h = intdiv($min, 60);
        $m = $min % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}