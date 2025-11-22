<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StampCorrectionStoreRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /** PG06: 自分の申請一覧（承認待ち／承認済み） */
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

        return view('request.request_index', compact('pending', 'approved'));
    }

    /** 修正申請の登録 */
    public function store(StampCorrectionStoreRequest $request)
    {
        $data = $request->validated();

        $attendance = Attendance::whereKey($data['attendance_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $requestedNote = $data['note'];

        $req = new StampCorrectionRequest([
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => $data['requested_clock_in_at'] ?? null,
            'requested_clock_out_at' => $data['requested_clock_out_at'] ?? null,
            'requested_break_minutes' => $data['requested_break_minutes'] ?? null,
            'requested_note' => $requestedNote,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);
        $req->save();

        $attendance->note = $requestedNote;
        $attendance->save();

        return redirect()->route('request.my_index')->with('ok', true);
    }
}