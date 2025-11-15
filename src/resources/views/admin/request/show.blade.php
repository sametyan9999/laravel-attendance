{{-- resources/views/admin/request/show.blade.php --}}
@php
    use Carbon\Carbon;

    /** @var \App\Models\StampCorrectionRequest $req */
    $attendance = $req->attendance;
    $user       = $attendance?->user;
    $name       = $user->name ?? '';

    // 日付
    $date = $attendance?->work_date
        ? Carbon::parse($attendance->work_date)
        : null;

    $yearText = $date ? $date->format('Y年') : '';
    $dayText  = $date ? $date->format('n月j日') : '';

    // 出勤・退勤：申請値があればそちらを優先
    $baseIn  = $req->requested_clock_in_at  ?? $attendance?->clock_in_at;
    $baseOut = $req->requested_clock_out_at ?? $attendance?->clock_out_at;

    $clockIn  = $baseIn  ? Carbon::parse($baseIn)->format('H:i')  : '';
    $clockOut = $baseOut ? Carbon::parse($baseOut)->format('H:i') : '';

    // 休憩1 / 休憩2（元の勤怠の休憩レコードをそのまま表示）
    $breaks = $attendance?->breaks?->sortBy('break_in_at')->values() ?? collect();
    $b1 = $breaks->get(0);
    $b2 = $breaks->get(1);

    $b1In  = $b1 && $b1->break_in_at  ? Carbon::parse($b1->break_in_at)->format('H:i')  : '';
    $b1Out = $b1 && $b1->break_out_at ? Carbon::parse($b1->break_out_at)->format('H:i') : '';
    $b2In  = $b2 && $b2->break_in_at  ? Carbon::parse($b2->break_in_at)->format('H:i')  : '';
    $b2Out = $b2 && $b2->break_out_at ? Carbon::parse($b2->break_out_at)->format('H:i') : '';

    // 備考：申請側のメモを表示
    $note = $req->requested_note ?? '';
@endphp

@extends('layouts.app')

@section('title','勤怠詳細（申請）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/request_show.css') }}">
@endpush

@section('content')
<div class="adm-attd-dtl">
  <div class="adm-attd-dtl__inner">

    {{-- 見出し --}}
    <h1 class="adm-attd-dtl__title">勤怠詳細</h1>

    {{-- カード本体 --}}
    <div class="adm-attd-dtl__card">

      {{-- 名前 --}}
      <div class="adm-attd-dtl__row">
        <div class="adm-attd-dtl__label">名前</div>
        <div class="adm-attd-dtl__value">
          {{ $name }}
        </div>
      </div>

      {{-- 日付 --}}
      <div class="adm-attd-dtl__row">
        <div class="adm-attd-dtl__label">日付</div>
        <div class="adm-attd-dtl__value adm-attd-dtl__value--date">
          <span class="adm-attd-dtl__date-year">{{ $yearText }}</span>
          <span class="adm-attd-dtl__date-day">{{ $dayText }}</span>
        </div>
      </div>

      {{-- 出勤・退勤 --}}
      <div class="adm-attd-dtl__row">
        <div class="adm-attd-dtl__label">出勤・退勤</div>
        <div class="adm-attd-dtl__value adm-attd-dtl__value--range">
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $clockIn }}"
                 readonly>
          <span class="adm-attd-dtl__tilde">〜</span>
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $clockOut }}"
                 readonly>
        </div>
      </div>

      {{-- 休憩１ --}}
      <div class="adm-attd-dtl__row">
        <div class="adm-attd-dtl__label">休憩</div>
        <div class="adm-attd-dtl__value adm-attd-dtl__value--range">
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $b1In }}"
                 readonly>
          <span class="adm-attd-dtl__tilde">〜</span>
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $b1Out }}"
                 readonly>
        </div>
      </div>

      {{-- 休憩２ --}}
      <div class="adm-attd-dtl__row">
        <div class="adm-attd-dtl__label">休憩２</div>
        <div class="adm-attd-dtl__value adm-attd-dtl__value--range">
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $b2In }}"
                 readonly>
          <span class="adm-attd-dtl__tilde">〜</span>
          <input type="text"
                 class="adm-attd-dtl__time-input"
                 value="{{ $b2Out }}"
                 readonly>
        </div>
      </div>

      {{-- 備考 --}}
      <div class="adm-attd-dtl__row adm-attd-dtl__row--note">
        <div class="adm-attd-dtl__label">備考</div>
        <div class="adm-attd-dtl__value">
          <input type="text"
                 class="adm-attd-dtl__note-input"
                 value="{{ $note }}"
                 readonly>
        </div>
      </div>
    </div>

    {{-- 「修正」ボタン（＝申請承認） --}}
    <div class="adm-attd-dtl__actions">
      <form method="POST"
            action="{{ route('admin.request.approve', ['stamp_request' => $req->id]) }}">
        @csrf
        <button type="submit" class="adm-attd-dtl__submit">
          修正
        </button>
      </form>
    </div>

  </div>
</div>
@endsection