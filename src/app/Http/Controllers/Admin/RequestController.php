<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $query = StampCorrectionRequest::with(['requester', 'attendance']);

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
     * PG13: 申請詳細
     */
    public function show(StampCorrectionRequest $stampRequest)
    {
        // ★ モデル側のリレーション名（requester / approver）に合わせる
        $stampRequest->load([
            'attendance.breaks',
            'attendance.user',
            'requester',
            'approver',
        ]);

        return view('admin.request.show', ['req' => $stampRequest]);
    }

    /**
     * PG13: 承認（「修正」ボタン）
     */
    public function approve(HttpRequest $http, StampCorrectionRequest $stampRequest)
    {
        // 任意メモ（承認時の管理者メモ等）を受け取る場合
        $data = $http->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($stampRequest, $data) {
            /** @var Attendance $attendance */
            $attendance = $stampRequest->attendance()->lockForUpdate()->firstOrFail();

            // 申請値が存在する項目のみ反映
            if ($stampRequest->requested_clock_in_at !== null) {
                $attendance->clock_in_at = $stampRequest->requested_clock_in_at;
            }
            if ($stampRequest->requested_clock_out_at !== null) {
                $attendance->clock_out_at = $stampRequest->requested_clock_out_at;
            }

            if ($stampRequest->requested_break_minutes !== null) {
                // 既存の休憩はクリアして、合計分のダミー1件で表現（集計を簡潔に）
                $attendance->breaks()->delete();

                $minutes = (int) $stampRequest->requested_break_minutes;
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
            $stampRequest->status      = 'approved';
            $stampRequest->approved_by = Auth::id();
            $stampRequest->approved_at = now();
            $stampRequest->save();
        });

        return back()->with('ok', true);
    }

    /**
     * PG13: 却下
     */
    public function reject(HttpRequest $http, StampCorrectionRequest $stampRequest)
    {
        // 却下理由を保存したい場合（任意）
        $data = $http->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($stampRequest, $data) {
            $stampRequest->status      = 'rejected';
            $stampRequest->approved_by = Auth::id();
            $stampRequest->approved_at = now();

            // 必要ならここで管理メモ用カラムに保存する想定
            // 例）$stampRequest->admin_note = $data['reason'] ?? null;

            $stampRequest->save();
        });

        return back()->with('ok', true);
    }
}