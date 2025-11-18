<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StampCorrectionStoreRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
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

        // ビューは resources/views/request/request_index.blade.php
        return view('request.request_index', compact('pending', 'approved'));
    }

    /**
     * 修正申請の登録（PG05のフォーム）
     */
    public function store(StampCorrectionStoreRequest $request)
    {
        // ★ FormRequest でバリデーション済み
        $data = $request->validated();

        // 本人の勤怠のみ対象（ポリシー未設定でも安全）
        $attendance = Attendance::whereKey($data['attendance_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // ★ フォームで入力された備考をそのまま申請理由にする
        $requestedNote = $data['note'];

        // 修正申請レコード作成
        $req = new StampCorrectionRequest([
            'attendance_id'            => $attendance->id,
            'requested_clock_in_at'    => $data['requested_clock_in_at']   ?? null,
            'requested_clock_out_at'   => $data['requested_clock_out_at']  ?? null,
            'requested_break_minutes'  => $data['requested_break_minutes'] ?? null,
            'requested_note'           => $requestedNote, // ★ ここが申請理由として保存される
            'status'                   => 'pending',
            'requested_by'             => Auth::id(),
        ]);
        $req->save();

        // ★ 勤怠側の備考にも同じ内容を入れておくと、勤怠詳細画面でも同じコメントが見える
        $attendance->note = $requestedNote;
        $attendance->save();

        return redirect()->route('request.my_index')->with('ok', true);
    }
}