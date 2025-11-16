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
use Illuminate\Support\Facades\Validator;

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
     * PG11: スタッフ別 月次一覧（1ヶ月分の日を全部出す）
     */
    public function byUser(Request $request, User $user)
    {
        // ?month=YYYY-MM があればその月、無ければ今月
        $month = $request->get('month');

        $base = $month
            ? CarbonImmutable::parse($month . '-01')->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();

        // この月の勤怠をまとめて取得して work_date でキー化
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function (Attendance $att) {
                return Carbon::parse($att->work_date)->format('Y-m-d');
            });

        $rows = [];

        // 1日〜末日までループして、無い日も1行作る
        for ($d = $start; $d <= $end; $d = $d->addDay()) {
            $key = $d->toDateString();
            /** @var Attendance|null $att */
            $att = $attendances->get($key);

            $breakMinutes = null;
            $totalMinutes = null;
            $clockIn      = null;
            $clockOut     = null;

            if ($att) {
                $clockIn  = $att->clock_in_at;
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

    /**
     * ★ PG11: スタッフ別 月次一覧 CSV 出力
     */
    public function byUserCsv(Request $request, User $user)
    {
        // ?month=YYYY-MM があればその月、無ければ今月
        $month = $request->get('month');

        $base = $month
            ? CarbonImmutable::parse($month . '-01')->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();

        // この月の勤怠をまとめて取得して work_date でキー化
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

            // ヘッダー行
            fputcsv($out, ['氏名', '日付', '出勤', '退勤', '休憩', '合計']);

            $fmtTime = function ($v) {
                if (!$v) return '';
                return Carbon::parse($v)->format('H:i');
            };

            $fmtHM = function ($min) {
                if (!is_numeric($min)) return '';
                $h = intdiv((int)$min, 60);
                $m = (int)$min % 60;
                return sprintf('%d:%02d', $h, $m);
            };

            for ($d = $start; $d <= $end; $d = $d->addDay()) {
                $key = $d->format('Y-m-d');
                /** @var Attendance|null $att */
                $att = $attendances->get($key);

                $clockIn  = $att?->clock_in_at;
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
     * （バリデーション内容・メッセージは機能要件 FN039 に合わせる）
     */
    public function update(Request $request, Attendance $attendance)
    {
        // 1次バリデーション（形式チェック）
        $rules = [
            // 出勤・退勤 … time型入力なので H:i 形式でチェック
            'clock_in_at'   => ['nullable', 'date_format:H:i'],
            'clock_out_at'  => ['nullable', 'date_format:H:i'],

            // 備考（★ 必須に変更）
            'note' => ['required', 'string', 'max:255'],

            // ステータス
            'status' => ['required', 'in:off_duty,working,break,completed'],

            // 休憩
            'breaks'                => ['array'],
            'breaks.*.break_in_at'  => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i'],
        ];

        $messages = [
            // 形式エラー
            'clock_in_at.date_format'           => '出勤時刻が不適切な値です',
            'clock_out_at.date_format'          => '退勤時刻が不適切な値です',
            'breaks.*.break_in_at.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',

            // 備考
            'note.required'                     => '備考を記入してください',
            'note.max'                          => '備考は255文字以内で入力してください',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // 2次バリデーション（前後関係のチェック）
        $validator->after(function ($validator) use ($request) {
            $ci = $request->input('clock_in_at');   // HH:MM or null
            $co = $request->input('clock_out_at');

            // 出勤時刻・退勤時刻の前後関係
            if ($ci !== null && $co !== null && $co < $ci) {
                // 機能要件の文言に合わせる
                $validator->errors()->add(
                    'clock_out_at',
                    '出勤時刻もしくは退勤時刻が不適切な値です'
                );
            }

            // 各休憩の前後関係
            foreach ((array) $request->input('breaks', []) as $idx => $b) {
                $bi = $b['break_in_at']  ?? null;
                $bo = $b['break_out_at'] ?? null;

                if ($bi !== null && $bo !== null && $bo < $bi) {
                    $validator->errors()->add(
                        "breaks.$idx.break_out_at",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });

        // バリデーション実行（エラー時は自動でリダイレクト）
        $data = $validator->validate();

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