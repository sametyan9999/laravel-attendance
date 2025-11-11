<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /**
     * PG06: 自分の申請一覧（承認待ち／承認済み）
     */
    public function myIndex()
    {
        $uid = Auth::id();

        $pending = StampCorrectionRequest::with('attendance')
            ->where('requested_by', $uid)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approved = StampCorrectionRequest::with('attendance')
            ->where('requested_by', $uid)
            ->where('status', 'approved')
            ->latest()
            ->get();

        // ビューは resources/views/request/my_index.blade.php を想定
        return view('request.my_index', compact('pending', 'approved'));
    }

    /**
     * 修正申請の登録（PG05のフォーム）
     */
    public function store(Request $request)
    {
        $messages = [
            'attendance_id.required'                 => '対象勤怠が不明です。',
            'requested_clock_in_at.date'             => '出勤時刻が不適切な値です',
            'requested_clock_out_at.date'            => '退勤時刻が不適切な値です',
            'requested_clock_out_at.after_or_equal'  => '退勤時間は出勤時間以降に設定してください',
            'requested_break_minutes.integer'        => '休憩時間が不適切な値です',
            'requested_break_minutes.min'            => '休憩時間が不適切な値です',
            'requested_note.required'                => '備考を記入してください',
        ];

        $data = $request->validate([
            'attendance_id'           => ['required', 'integer', 'exists:attendances,id'],
            'requested_clock_in_at'   => ['nullable', 'date'],
            'requested_clock_out_at'  => ['nullable', 'date', 'after_or_equal:requested_clock_in_at'],
            'requested_break_minutes' => ['nullable', 'integer', 'min:0'],
            'requested_note'          => ['required', 'string', 'max:255'],
        ], $messages);

        // 本人の勤怠のみ対象（ポリシー未設定でも安全）
        $attendance = Attendance::whereKey($data['attendance_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $req = new StampCorrectionRequest([
            'attendance_id'            => $attendance->id,
            'requested_clock_in_at'    => $data['requested_clock_in_at']   ?? null,
            'requested_clock_out_at'   => $data['requested_clock_out_at']  ?? null,
            'requested_break_minutes'  => $data['requested_break_minutes'] ?? null,
            'requested_note'           => $data['requested_note']          ?? null,
            'status'                   => 'pending',
            'requested_by'             => Auth::id(),
        ]);
        $req->save();

        return redirect()->route('request.my_index')->with('ok', true);
    }
}