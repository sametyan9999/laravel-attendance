{{-- resources/views/attendance/detail.blade.php --}}
@php
  use Carbon\Carbon;

  // 表示用変数
  $userName = optional($attendance->user)->name ?? auth()->user()->name;
  $d = Carbon::parse($attendance->work_date);

  $breaks = $attendance->breaks->sortBy('break_in_at')->values();
  $b1 = $breaks->get(0);
  $b2 = $breaks->get(1);
  // 3 回目以降の休憩
  $extraBreaks = $breaks->slice(2)->values();

  $ci  = $attendance->clock_in_at  ? Carbon::parse($attendance->clock_in_at)->format('H:i')  : '';
  $co  = $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';
  $b1i = $b1 && $b1->break_in_at   ? Carbon::parse($b1->break_in_at)->format('H:i')          : '';
  $b1o = $b1 && $b1->break_out_at  ? Carbon::parse($b1->break_out_at)->format('H:i')         : '';
  $b2i = $b2 && $b2->break_in_at   ? Carbon::parse($b2->break_in_at)->format('H:i')          : '';
  $b2o = $b2 && $b2->break_out_at  ? Carbon::parse($b2->break_out_at)->format('H:i')         : '';

  // 承認待ちで編集ロック
  $locked = !empty($hasPending);
@endphp

@extends('layouts.app')

@section('title','勤怠詳細')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/attendance/detail.css') }}?v={{ filemtime(public_path('css/attendance/detail.css')) }}">
@endpush

@section('content')
<div class="dtl-container">
  <h1 class="dtl-title">勤怠詳細</h1>

  @if ($errors->any())
    <div class="dtl-errors" role="alert">
      <ul>
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form class="dtl-card" method="POST" action="{{ route('attendance.update', $attendance) }}">
    @csrf
    @method('PUT')

    {{-- 名前 --}}
    <div class="dtl-row">
      <div class="dtl-th">名前</div>
      <div class="dtl-td dtl-strong">{{ $userName }}</div>
    </div>

    {{-- 日付 --}}
    <div class="dtl-row">
      <div class="dtl-th">日付</div>
      <div class="dtl-td">
        <div class="dtl-date">
          <span class="dtl-year">{{ $d->year }}年</span>
          <span class="dtl-md">{{ $d->month }}月{{ $d->day }}日</span>
        </div>
      </div>
    </div>

    {{-- 出勤・退勤 --}}
    <div class="dtl-row">
      <div class="dtl-th">出勤・退勤</div>
      <div class="dtl-td">
        <div class="dtl-range">
          @if(!$locked)
            <input type="time" name="clock_in"
                   value="{{ old('clock_in', $ci) }}"
                   class="dtl-time">
            <span class="dtl-tilde">〜</span>
            <input type="time" name="clock_out"
                   value="{{ old('clock_out', $co) }}"
                   class="dtl-time">
          @else
            <span class="dtl-text">{{ $ci ?: '—' }}</span>
            <span class="dtl-tilde">〜</span>
            <span class="dtl-text">{{ $co ?: '—' }}</span>
          @endif
        </div>
      </div>
    </div>

    {{-- 休憩１ --}}
    <div class="dtl-row">
      <div class="dtl-th">休憩</div>
      <div class="dtl-td">
        <div class="dtl-range">
          @if(!$locked)
            <input type="time" name="break1_in"
                   value="{{ old('break1_in', $b1i) }}"
                   class="dtl-time">
            <span class="dtl-tilde">〜</span>
            <input type="time" name="break1_out"
                   value="{{ old('break1_out', $b1o) }}"
                   class="dtl-time">
          @else
            <span class="dtl-text">{{ $b1i ?: '—' }}</span>
            <span class="dtl-tilde">〜</span>
            <span class="dtl-text">{{ $b1o ?: '—' }}</span>
          @endif
        </div>
      </div>
    </div>

    {{-- 休憩２ --}}
    <div class="dtl-row">
      <div class="dtl-th">休憩2</div>
      <div class="dtl-td">
        <div class="dtl-range">
          @if(!$locked)
            <input type="time" name="break2_in"
                   value="{{ old('break2_in', $b2i) }}"
                   class="dtl-time">
            <span class="dtl-tilde">〜</span>
            <input type="time" name="break2_out"
                   value="{{ old('break2_out', $b2o) }}"
                   class="dtl-time">
          @else
            <span class="dtl-text">{{ $b2i ?: '—' }}</span>
            <span class="dtl-tilde">〜</span>
            <span class="dtl-text">{{ $b2o ?: '—' }}</span>
          @endif
        </div>
      </div>
    </div>

    {{-- 休憩３以降 --}}
    @foreach($extraBreaks as $index => $br)
      @php
        $n = $index + 3;  // 3,4,5...
        $exIn  = $br->break_in_at  ? Carbon::parse($br->break_in_at)->format('H:i') : '';
        $exOut = $br->break_out_at ? Carbon::parse($br->break_out_at)->format('H:i') : '';
        $exInName  = "break{$n}_in";
        $exOutName = "break{$n}_out";
      @endphp
      <div class="dtl-row">
        <div class="dtl-th">休憩{{ $n }}</div>
        <div class="dtl-td">
          <div class="dtl-range">
            @if(!$locked)
              <input type="time" name="{{ $exInName }}"
                     value="{{ old($exInName, $exIn) }}"
                     class="dtl-time">
              <span class="dtl-tilde">〜</span>
              <input type="time" name="{{ $exOutName }}"
                     value="{{ old($exOutName, $exOut) }}"
                     class="dtl-time">
            @else
              <span class="dtl-text">{{ $exIn ?: '—' }}</span>
              <span class="dtl-tilde">〜</span>
              <span class="dtl-text">{{ $exOut ?: '—' }}</span>
            @endif
          </div>
        </div>
      </div>
    @endforeach

    {{-- 備考 --}}
    <div class="dtl-row">
      <div class="dtl-th">備考</div>
      <div class="dtl-td">
        @if(!$locked)
          <input type="text" name="note"
                 value="{{ old('note', $attendance->note ?? '') }}"
                 class="dtl-note"
                 placeholder="例）電車遅延のため">
        @else
          <span class="dtl-text dtl-text--wide">{{ $attendance->note ?? '—' }}</span>
        @endif
      </div>
    </div>

    {{-- フッター（カード内は修正ボタンだけ） --}}
    <div class="dtl-actions">
      @if(!$locked)
        <button type="submit" class="dtl-submit">修正</button>
      @endif
    </div>
  </form>

  {{-- ★ 承認待ちメッセージ：カードの外・右下 --}}
  @if($locked)
    <p class="dtl-pending">※承認待ちのため修正はできません。</p>
  @endif
</div>
@endsection