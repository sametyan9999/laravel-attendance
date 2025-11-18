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
    /**
     * PG12: 申請一覧（承認待ち / 承認済み）
     */
    public function index(HttpRequest $http)
    {
        // ?tab=pending / approved でタブを切り替え
        $tab = $http->query('tab', 'pending');

        $query = StampCorrectionRequest::with([
            'requester',
            'attendance',
        ]);

        if ($tab === 'approved') {
            $query->where('status', 'approved');
        } else {
            // それ以外は強制的に pending に寄せる
            $tab = 'pending';
            $query->where('status', 'pending');
        }

        $requests = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.request.index', [
            'requests' => $requests,
            'tab'      => $tab,
        ]);
    }

    /**
     * ✅ テスト用: 承認済み一覧
     *
     * GET /admin/stamp_correction_request/approved
     * → 「修正申請」を含む一覧画面を返す
     */
    public function approved()
    {
        $requests = StampCorrectionRequest::with([
                'requester',
                'attendance',
            ])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->paginate(20);

        // 承認済みタブとして同じビューを再利用
        return view('admin.request.index', [
            'requests' => $requests,
            'tab'      => 'approved',
        ]);
    }

    /**
     * PG13: 申請詳細
     *
     * ルート定義:
     * GET /admin/stamp_correction_request/approve/{attendance_correct_request_id}
     */
    public function show(StampCorrectionRequest $attendance_correct_request_id)
    {
        // モデル側のリレーション名（requester / approver）に合わせる
        $attendance_correct_request_id->load([
            'attendance.breaks',
            'attendance.user',
            'requester',
            'approver',
        ]);

        // Blade では $req として扱う
        return view('admin.request.show', ['req' => $attendance_correct_request_id]);
    }

    /**
     * PG13: 承認（「承認」ボタン）
     *
     * ルート定義:
     * POST /admin/stamp_correction_request/{attendance_correct_request_id}/approve
     *
     * → RequestApproveRequest にバリデーションを移動
     */
    public function approve(RequestApproveRequest $request, StampCorrectionRequest $attendance_correct_request_id)
    {
        $data = $request->validated();

        DB::transaction(function () use ($attendance_correct_request_id, $data) {
            /** @var Attendance $attendance */
            $attendance = $attendance_correct_request_id
                ->attendance()
                ->lockForUpdate()
                ->firstOrFail();

            // 申請値が存在する項目のみ反映
            if ($attendance_correct_request_id->requested_clock_in_at !== null) {
                $attendance->clock_in_at = $attendance_correct_request_id->requested_clock_in_at;
            }
            if ($attendance_correct_request_id->requested_clock_out_at !== null) {
                $attendance->clock_out_at = $attendance_correct_request_id->requested_clock_out_at;
            }

            if ($attendance_correct_request_id->requested_break_minutes !== null) {
                // 既存の休憩はクリアして、合計分のダミー1件で表現（集計を簡潔に）
                $attendance->breaks()->delete();

                $minutes = (int) $attendance_correct_request_id->requested_break_minutes;
                if ($minutes > 0) {
                    $in = $attendance->clock_in_at ?? now();
                    $out = $in->copy()->addMinutes($minutes);

                    // 念のため順序ガード（負の休憩防止）
                    if ($out->lessThan($in)) {
                        $out = $in;
                    }

                    $attendance->breaks()->create([
                        'break_in_at'  => $in,
                        'break_out_at' => $out,
                    ]);
                }
            }

            // ステータス自動更新
            $attendance->status = $attendance->clock_out_at
                ? 'completed'
                : ($attendance->clock_in_at ? 'working' : 'off_duty');

            // 管理メモを利用する場合（attendance に note を持たせている前提）
            if (!empty($data['note'])) {
                $attendance->note = trim((string) $data['note']);
            }

            $attendance->save();

            // 申請ステータス更新
            $attendance_correct_request_id->status      = 'approved';
            $attendance_correct_request_id->approved_by = Auth::id();
            $attendance_correct_request_id->approved_at = now();
            $attendance_correct_request_id->save();
        });

        // 承認後はこの申請の詳細画面へ
        return redirect()
            ->route('admin.request.show', [
                'attendance_correct_request_id' => $attendance_correct_request_id->id,
            ])
            ->with('ok', true);
    }

    /**
     * PG13: 却下
     *
     * → RequestRejectRequest にバリデーションを移動
     */
    public function reject(RequestRejectRequest $request, StampCorrectionRequest $stampRequest)
    {
        $data = $request->validated();

        DB::transaction(function () use ($stampRequest, $data) {
            $stampRequest->status      = 'rejected';
            $stampRequest->approved_by = Auth::id();
            $stampRequest->approved_at = now();

            // 必要ならここで管理メモ用カラムに保存する想定
            // 例）$stampRequest->admin_note = $data['reason'] ?? null;

            $stampRequest->save();
        });

        // 却下後は承認待ちタブに戻す
        return redirect()
            ->route('admin.request.index', ['tab' => 'pending'])
            ->with('ok', true);
    }
}