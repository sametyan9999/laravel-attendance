{{-- resources/views/attendance/today.blade.php --}}
@extends('layouts.app')

@section('title', '勤怠登録')

@section('content')
  {{-- ページ専用CSS --}}
  <link rel="stylesheet" href="{{ asset('css/attendance/today.css') }}"/>

  @php
    // ★ ここで「日本語ロケール＋タイムゾーン」を確実に適用した now を用意
    $nowJp = $now->copy()
                 ->timezone(config('app.timezone'))
                 ->locale('ja');
  @endphp

  <div class="attd-wrap">
    <div class="attd-inner">
      {{-- ステータスピル --}}
      @php
        $label = [
          'off_duty'  => '勤務外',
          'working'   => '出勤中',
          'break'     => '休憩中',
          'completed' => '退勤済'
        ][$attendance->status] ?? '勤務外';
      @endphp
      <div class="attd-status">
        <span class="pill pill-{{ $attendance->status }}">{{ $label }}</span>
      </div>

      {{-- 日付（YYYY年M月D日(ddd)） --}}
      <div class="attd-date">
        {{ $nowJp->isoFormat('YYYY年M月D日(ddd)') }}
      </div>

      {{-- 現在時刻（HH:MM） --}}
      <div class="attd-clock">
        {{ $nowJp->format('H:i') }}
      </div>

      {{-- アクション（working時のみ横並び、break時は白ボタン用クラス） --}}
      <div class="attd-actions
        {{ $attendance->status === 'working' ? 'attd-actions--row' : '' }}
        {{ $attendance->status === 'break' ? 'break-mode' : '' }}">
        @if ($attendance->status === 'off_duty')
          <form action="{{ route('attendance.clock_in') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-main">出勤</button>
          </form>

        @elseif ($attendance->status === 'working')
          {{-- 左：退勤（黒）／右：休憩入（白） --}}
          <form action="{{ route('attendance.clock_out') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-main">退勤</button>
          </form>
          <form action="{{ route('attendance.break_in') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sub">休憩入</button>
          </form>

        @elseif ($attendance->status === 'break')
          <form action="{{ route('attendance.break_out') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-main">休憩戻</button>
          </form>

        @elseif ($attendance->status === 'completed')
          <p class="attd-done">お疲れ様でした。</p>
        @endif
      </div>

      {{-- エラーのみ表示（フラッシュは非表示） --}}
      @if ($errors->any())
        <div class="attd-errors">
          <ul>
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>
@endsection