<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
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
     * Blade が扱いやすいように、Carbon と配列で渡す
     */
    public function indexMonthly(Request $request)
    {
        $user = Auth::user();

        // 互換性のある取得方法（string() は使わない）
        $monthStr = (string) $request->query('month', '');

        // YYYY-MM のみ許容して CarbonImmutable に正規化
        if (preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            $base = CarbonImmutable::parse($monthStr . '-01')->startOfMonth();
        } else {
            $base = CarbonImmutable::now()->startOfMonth();
        }

        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();

        // 当月の勤怠（休憩含む）を取得
        // 🚩 ここが重要：キーを "Y-m-d" に固定して keyBy する
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(function ($r) {
                // work_date が Carbon でも文字列でも確実に "Y-m-d" に揃える
                return Carbon::parse($r->work_date)->format('Y-m-d');
            });

        // Blade 側のテーブルロジックに合わせた配列を用意
        // - $days: Carbon の配列（各日）
        // - $records: 'Y-m-d' => ['id', 'clock_in', 'clock_out', 'break_minutes', 'total_minutes', 'break_hm', 'work_hm']
        $days = [];
        $records = [];

        for ($d = $start; $d <= $end; $d = $d->addDay()) {
            $days[] = $d;
            /** @var Attendance|null $att */
            $att = $attendances->get($d->toDateString());

            if ($att) {
                // 休憩合計（分）
                $breakMin = 0;
                foreach ($att->breaks as $b) {
                    if ($b->break_in_at && $b->break_out_at) {
                        $breakMin += Carbon::parse($b->break_out_at)->diffInMinutes(Carbon::parse($b->break_in_at));
                    }
                }

                // 勤務合計（分）= 退勤 - 出勤 - 休憩
                $totalMin = null;
                if ($att->clock_in_at && $att->clock_out_at) {
                    $totalMin = Carbon::parse($att->clock_out_at)->diffInMinutes(Carbon::parse($att->clock_in_at)) - $breakMin;
                    if ($totalMin < 0) $totalMin = 0;
                }

                // 表示用の H:MM も保持（Blade で使う/デバッグしやすい）
                $breakHm = $breakMin ? $this->minToHM($breakMin) : '—';
                $workHm  = is_int($totalMin) ? $this->minToHM($totalMin) : '—';

                $records[$d->toDateString()] = [
                    'id'             => $att->id,
                    'clock_in'       => $att->clock_in_at,   // Carbon(キャスト済) or string
                    'clock_out'      => $att->clock_out_at,  // Carbon(キャスト済) or string
                    'break_minutes'  => $breakMin ?: null,
                    'total_minutes'  => $totalMin,
                    'break_hm'       => $breakHm,
                    'work_hm'        => $workHm,
                ];
            }
        }

        // Blade に Carbon / 配列を渡す
        return view('attendance.list', [
            'month'     => $base,                 // CarbonImmutable
            'prevMonth' => $base->subMonth(),     // CarbonImmutable
            'nextMonth' => $base->addMonth(),     // CarbonImmutable
            'days'      => $days,                 // CarbonImmutable[]（1日ごと）
            'records'   => $records,              // 'Y-m-d' => [...]
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