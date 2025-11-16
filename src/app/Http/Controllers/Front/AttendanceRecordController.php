<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceRecordController extends Controller
{
    /** PG03: 本日の打刻画面 */
    public function today(Request $request)
    {
        $user = Auth::user();

        // ★ タイムゾーンを明示した「今」
        $now = CarbonImmutable::now()->timezone(config('app.timezone'));

        // その日の「日付」だけ切り出し
        $today = $now->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['status' => 'off_duty']
        )->load('breaks');

        return view('attendance.today', [
            'attendance' => $attendance,
            'now'        => $now,
        ]);
    }

    /** 出勤 */
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

    /** 休憩入 */
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

    /** 休憩戻 */
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

    /** 退勤 */
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

    /** PG04: 月次一覧 */
    public function indexMonthly(Request $request)
    {
        $user = Auth::user();

        $monthStr = (string) $request->query('month', '');
        if (preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            $base = CarbonImmutable::parse($monthStr . '-01')->startOfMonth();
        } else {
            $base = CarbonImmutable::now()->startOfMonth();
        }

        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->work_date)->format('Y-m-d'));

        $days    = [];
        $records = [];

        for ($d = $start; $d <= $end; $d = $d->addDay()) {
            $days[] = $d;
            /** @var Attendance|null $att */
            $att = $attendances->get($d->toDateString());

            if ($att) {
                $breakMin = 0;
                foreach ($att->breaks as $b) {
                    if ($b->break_in_at && $b->break_out_at) {
                        $breakMin += Carbon::parse($b->break_out_at)
                            ->diffInMinutes(Carbon::parse($b->break_in_at));
                    }
                }

                $totalMin = null;
                if ($att->clock_in_at && $att->clock_out_at) {
                    $totalMin = Carbon::parse($att->clock_out_at)
                        ->diffInMinutes(Carbon::parse($att->clock_in_at)) - $breakMin;
                    if ($totalMin < 0) {
                        $totalMin = 0;
                    }
                }

                $records[$d->toDateString()] = [
                    'id'            => $att->id,
                    'clock_in'      => $att->clock_in_at,
                    'clock_out'     => $att->clock_out_at,
                    'break_minutes' => $breakMin ?: null,
                    'total_minutes' => $totalMin,
                ];
            }
        }

        return view('attendance.list', [
            'month'     => $base,
            'prevMonth' => $base->subMonth(),
            'nextMonth' => $base->addMonth(),
            'days'      => $days,
            'records'   => $records,
        ]);
    }

    /** PG05: 日次詳細（表示） */
    public function detail(Attendance $attendance)
    {
        $attendance->load('breaks', 'user');
        $this->authorize('view', $attendance);

        $pendingRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $hasPending = !is_null($pendingRequest);

        return view('attendance.detail', [
            'attendance'     => $attendance,
            'hasPending'     => $hasPending,
            'pendingRequest' => $pendingRequest,
        ]);
    }

    /** PG05: 日次詳細（更新＝修正申請の作成） */
    public function update(Request $request, Attendance $attendance)
    {
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        $hasPending = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return back()->withErrors(['pending' => '承認待ちのため修正はできません。'])->withInput();
        }

        // ★ メッセージ（備考以外は validateTimeRelations() で上書き）
        $messages = [
            'clock_in.date_format'   => '出勤時刻は「HH:MM」形式で入力してください。',
            'clock_out.date_format'  => '退勤時刻は「HH:MM」形式で入力してください。',
            'break1_in.date_format'  => '休憩1の開始時刻は「HH:MM」形式で入力してください。',
            'break1_out.date_format' => '休憩1の終了時刻は「HH:MM」形式で入力してください。',
            'break2_in.date_format'  => '休憩2の開始時刻は「HH:MM」形式で入力してください。',
            'break2_out.date_format' => '休憩2の終了時刻は「HH:MM」形式で入力してください。',
            'note.required'          => '備考を記入してください',
        ];

        $validated = $request->validate([
            'clock_in'   => ['nullable', 'date_format:H:i'],
            'clock_out'  => ['nullable', 'date_format:H:i'],
            'break1_in'  => ['nullable', 'date_format:H:i'],
            'break1_out' => ['nullable', 'date_format:H:i'],
            'break2_in'  => ['nullable', 'date_format:H:i'],
            'break2_out' => ['nullable', 'date_format:H:i'],
            'note'       => ['required', 'string', 'max:255'],
        ], $messages);

        // ★ ユーザー要件に合わせた相関バリデーション
        $this->validateTimeRelations($attendance->work_date, $validated);

        // 時刻変換
        $toDT = function ($hm) use ($attendance) {
            return $hm
                ? Carbon::createFromFormat(
                    'Y-m-d H:i',
                    Carbon::parse($attendance->work_date)->format('Y-m-d') . ' ' . $hm
                )
                : null;
        };

        $reqClockIn  = $toDT($validated['clock_in']  ?? null);
        $reqClockOut = $toDT($validated['clock_out'] ?? null);

        // 休憩合計（分）
        $pairs = [
            [$validated['break1_in'] ?? null, $validated['break1_out'] ?? null],
            [$validated['break2_in'] ?? null, $validated['break2_out'] ?? null],
        ];

        $extraInputs = $request->input('extra_breaks', []);
        foreach ($extraInputs as $ex) {
            $iHm = $ex['in']  ?? null;
            $oHm = $ex['out'] ?? null;
            if (
                $iHm && $oHm &&
                preg_match('/^\d{2}:\d{2}$/', $iHm) &&
                preg_match('/^\d{2}:\d{2}$/', $oHm)
            ) {
                $pairs[] = [$iHm, $oHm];
            }
        }

        $breakMin = 0;
        foreach ($pairs as [$i, $o]) {
            if ($i && $o) {
                $ii = $toDT($i);
                $oo = $toDT($o);
                if ($ii && $oo && $oo->greaterThan($ii)) {
                    $breakMin += $oo->diffInMinutes($ii);
                }
            }
        }
        $breakMin = $breakMin > 0 ? $breakMin : null;

        // 保存処理
        DB::transaction(function () use ($attendance, $validated, $reqClockIn, $reqClockOut, $breakMin) {
            $attendance->note = $validated['note'] ?? null;
            $attendance->save();

            StampCorrectionRequest::create([
                'attendance_id'           => $attendance->id,
                'requested_by'            => Auth::id(),
                'status'                  => 'pending',
                'requested_clock_in_at'   => $reqClockIn,
                'requested_clock_out_at'  => $reqClockOut,
                'requested_break_minutes' => $breakMin,
                'requested_note'          => $attendance->note,
            ]);
        });

        return redirect()
            ->route('attendance.detail', $attendance)
            ->with('ok', true);
    }

    /** ★ 要件に合わせたバリデーション（修正済み） */
    private function validateTimeRelations($workDate, array $v): void
    {
        $toDT = function ($hm) use ($workDate) {
            return $hm
                ? Carbon::createFromFormat(
                    'Y-m-d H:i',
                    Carbon::parse($workDate)->format('Y-m-d') . ' ' . $hm
                )
                : null;
        };

        $ci = $toDT($v['clock_in']  ?? null);
        $co = $toDT($v['clock_out'] ?? null);

        $b1i = $toDT($v['break1_in']  ?? null);
        $b1o = $toDT($v['break1_out'] ?? null);
        $b2i = $toDT($v['break2_in']  ?? null);
        $b2o = $toDT($v['break2_out'] ?? null);

        $errors = [];

        // ① 出勤 > 退勤
        if ($ci && $co && $co->lessThanOrEqualTo($ci)) {
            // ★ 仕様どおりのキーと文言
            $errors['clock_out'] = '出勤時間もしくは退勤時間が不適切な値です';
        }

        // ③ 休憩終了 <= 開始
        if ($b1i && $b1o && $b1o->lessThanOrEqualTo($b1i)) {
            $errors['break1_out'] = '休憩時間もしくは退勤時間が不適切な値です';
        }
        if ($b2i && $b2o && $b2o->lessThanOrEqualTo($b2i)) {
            $errors['break2_out'] = '休憩時間もしくは退勤時間が不適切な値です';
        }

        // ②・③ 勤務時間の範囲外チェック
        foreach (
            [
                ['i' => $b1i, 'o' => $b1o, 'k' => 'break1_in'],
                ['i' => $b2i, 'o' => $b2o, 'k' => 'break2_in'],
            ] as $bk
        ) {
            $keyIn  = $bk['k'];
            $keyOut = str_replace('_in', '_out', $keyIn);

            // 休憩開始が勤務時間の外
            if ($bk['i']) {
                if (($ci && $bk['i']->lessThan($ci)) || ($co && $bk['i']->greaterThan($co))) {
                    $errors[$keyIn] = '休憩時間が不適切な値です';
                }
            }

            // 休憩終了が勤務時間の外
            if ($bk['o']) {
                if (($ci && $bk['o']->lessThan($ci)) || ($co && $bk['o']->greaterThan($co))) {
                    $errors[$keyOut] = '休憩時間もしくは退勤時間が不適切な値です';
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** 遷移の共通処理 */
    private function transition(\Closure $handler)
    {
        $user = Auth::user();

        // ★ タイムゾーン統一
        $now   = CarbonImmutable::now()->timezone(config('app.timezone'));
        $today = $now->toDateString();

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

    private function minToHM(int $min): string
    {
        $h = intdiv($min, 60);
        $m = $min % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}