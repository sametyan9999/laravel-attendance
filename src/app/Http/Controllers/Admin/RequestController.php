<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RequestApproveRequest;
use App\Http\Requests\Admin\RequestRejectRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(HttpRequest $http)
    {
        $tab = $http->query('tab', 'pending');

        $query = StampCorrectionRequest::with(['requester', 'attendance']);

        if ($tab === 'approved') {
            $query->where('status', 'approved');
        } else {
            $tab = 'pending';
            $query->where('status', 'pending');
        }

        $requests = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.request.index', [
            'requests' => $requests,
            'tab'      => $tab,
        ]);
    }

    public function approved()
    {
        $requests = StampCorrectionRequest::with(['requester', 'attendance'])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.request.index', [
            'requests' => $requests,
            'tab'      => 'approved',
        ]);
    }

    public function show(StampCorrectionRequest $attendance_correct_request_id)
    {
        $attendance_correct_request_id->load([
            'attendance.breaks',
            'attendance.user',
            'requester',
            'approver',
        ]);

        return view('admin.request.show', ['req' => $attendance_correct_request_id]);
    }

    public function approve(RequestApproveRequest $request, StampCorrectionRequest $attendance_correct_request_id)
    {
        $data = $request->validated();

        DB::transaction(function () use ($attendance_correct_request_id, $data) {
            $attendance = $attendance_correct_request_id->attendance()
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance_correct_request_id->requested_clock_in_at !== null) {
                $attendance->clock_in_at = $attendance_correct_request_id->requested_clock_in_at;
            }

            if ($attendance_correct_request_id->requested_clock_out_at !== null) {
                $attendance->clock_out_at = $attendance_correct_request_id->requested_clock_out_at;
            }

            if ($attendance_correct_request_id->requested_break_minutes !== null) {
                $attendance->breaks()->delete();

                $minutes = (int) $attendance_correct_request_id->requested_break_minutes;
                if ($minutes > 0) {
                    $in = $attendance->clock_in_at ?? now();
                    $out = $in->copy()->addMinutes($minutes);

                    if ($out->lessThan($in)) {
                        $out = $in;
                    }

                    $attendance->breaks()->create([
                        'break_in_at'  => $in,
                        'break_out_at' => $out,
                    ]);
                }
            }

            $attendance->status = $attendance->clock_out_at
                ? 'completed'
                : ($attendance->clock_in_at ? 'working' : 'off_duty');

            if (!empty($data['note'])) {
                $attendance->note = trim((string)$data['note']);
            }

            $attendance->save();

            $attendance_correct_request_id->status = 'approved';
            $attendance_correct_request_id->approved_by = Auth::id();
            $attendance_correct_request_id->approved_at = now();
            $attendance_correct_request_id->save();
        });

        return redirect()
            ->route('admin.request.show', [
                'attendance_correct_request_id' => $attendance_correct_request_id->id,
            ])
            ->with('ok', true);
    }

    public function reject(RequestRejectRequest $request, StampCorrectionRequest $stampRequest)
    {
        $data = $request->validated();

        DB::transaction(function () use ($stampRequest) {
            $stampRequest->status = 'rejected';
            $stampRequest->approved_by = Auth::id();
            $stampRequest->approved_at = now();
            $stampRequest->save();
        });

        return redirect()
            ->route('admin.request.index', ['tab' => 'pending'])
            ->with('ok', true);
    }
}