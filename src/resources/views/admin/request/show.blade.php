{{-- resources/views/admin/request/show.blade.php --}}
@php
    use Carbon\Carbon;

    /** @var \App\Models\StampCorrectionRequest $req */
    $attendance = $req->attendance;
    $user       = $attendance->user;

    $workDate = Carbon::parse($attendance->work_date);

    // ★ 申請された出勤・退勤（なければ勤怠の値、さらに無ければ --:--）
    $ciRequested = $req->requested_clock_in_at;
    $coRequested = $req->requested_clock_out_at;

    $ci = $ciRequested
        ? Carbon::parse($ciRequested)->format('H:i')
        : ($attendance->clock_in_at
            ? Carbon::parse($attendance->clock_in_at)->format('H:i')
            : '--:--');

    $co = $coRequested
        ? Carbon::parse($coRequested)->format('H:i')
        : ($attendance->clock_out_at
            ? Carbon::parse($attendance->clock_out_at)->format('H:i')
            : '--:--');

    // ★ 申請された休憩合計（分）→ HH:MM 表示
    if (is_numeric($req->requested_break_minutes)) {
        $totalMin  = (int) $req->requested_break_minutes;
        $breakHour = intdiv($totalMin, 60);
        $breakMin  = $totalMin % 60;
        $breakText = sprintf('%02d:%02d', $breakHour, $breakMin);
    } else {
        // 申請が無ければ 00:00（参考画像に合わせる）
        $breakText = '00:00';
    }

    // ★ 申請備考（なければ「ー」）
    $noteText = ($req->requested_note !== null && $req->requested_note !== '')
        ? $req->requested_note
        : 'ー';

    // ★ 「承認済み」判定は必ず StampCorrectionRequest.status だけを見る
    $isApproved = $req->status === 'approved';
@endphp

@extends('layouts.app')

@section('title', '勤怠詳細（修正申請承認）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_detail.css') }}">
@endpush

@section('content')
    <div class="adm-attd-dtl">
        <div class="adm-attd-dtl__inner">

            <h1 class="adm-attd-dtl__title">勤怠詳細</h1>

            {{-- 承認ボタン用フォーム --}}
            <form method="POST"
                  action="{{ $isApproved
                        ? '#'  {{-- 承認済みのときは送信しない --}}
                        : route('admin.request.approve', ['stamp_request' => $req->id]) }}"
                  class="adm-attd-dtl__form">
                @csrf

                {{-- ===== カード本体 ===== --}}
                <div class="adm-attd-dtl__card">
                    {{-- 名前 --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">名前</div>
                        <div class="adm-attd-dtl__td">
                            <span class="adm-attd-dtl__name">
                                {{ $user->name ?? '' }}
                            </span>
                        </div>
                    </div>

                    {{-- 日付 --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">日付</div>
                        <div class="adm-attd-dtl__td adm-attd-dtl__td--date">
                            <span class="adm-attd-dtl__date-year">
                                {{ $workDate->format('Y年') }}
                            </span>
                            <span class="adm-attd-dtl__date-main">
                                {{ $workDate->format('n月j日') }}
                            </span>
                        </div>
                    </div>

                    {{-- 出勤・退勤（文字列） --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">出勤・退勤</div>
                        <div class="adm-attd-dtl__td">
                            <div class="adm-attd-dtl__time-range">
                                <span class="adm-attd-dtl__time-text">{{ $ci }}</span>
                                <span class="adm-attd-dtl__tilde">〜</span>
                                <span class="adm-attd-dtl__time-text">{{ $co }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- 休憩（合計 HH:MM） --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">休憩</div>
                        <div class="adm-attd-dtl__td">
                            <span class="adm-attd-dtl__time-text">{{ $breakText }}</span>
                        </div>
                    </div>

                    {{-- 備考（申請理由） --}}
                    <div class="adm-attd-dtl__row adm-attd-dtl__row--note">
                        <div class="adm-attd-dtl__th">備考</div>
                        <div class="adm-attd-dtl__td">
                            <p class="adm-attd-dtl__note-text">
                                {{ $noteText }}
                            </p>
                        </div>
                    </div>
                </div>{{-- /.adm-attd-dtl__card --}}

                {{-- ボタン行：承認 or 承認済み --}}
                <div class="adm-attd-dtl__actions">
                    @if($isApproved)
                        <div class="adm-attd-dtl__submit adm-attd-dtl__submit--done">
                            承認済み
                        </div>
                    @else
                        <button type="submit" class="adm-attd-dtl__submit">
                            承認
                        </button>
                    @endif
                </div>
            </form>

        </div>
    </div>
@endsection