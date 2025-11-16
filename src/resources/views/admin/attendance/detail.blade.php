{{-- resources/views/admin/attendance/detail.blade.php --}}
@php
    use Carbon\Carbon;

    $user     = $attendance->user;
    $workDate = Carbon::parse($attendance->work_date);

    // 休憩は開始時刻順に並べ替え
    $breaks = $attendance->breaks->sortBy('break_in_at')->values();

    // 出勤・退勤
    $ci = $attendance->clock_in_at
        ? Carbon::parse($attendance->clock_in_at)->format('H:i')
        : '';

    $co = $attendance->clock_out_at
        ? Carbon::parse($attendance->clock_out_at)->format('H:i')
        : '';

    // 入力用に 2 行分の休憩枠を用意（休憩 / 休憩2）
    $firstBreak  = $breaks->get(0);
    $secondBreak = $breaks->get(1);

    $b1_in  = $firstBreak && $firstBreak->break_in_at
              ? Carbon::parse($firstBreak->break_in_at)->format('H:i') : '';
    $b1_out = $firstBreak && $firstBreak->break_out_at
              ? Carbon::parse($firstBreak->break_out_at)->format('H:i') : '';

    $b2_in  = $secondBreak && $secondBreak->break_in_at
              ? Carbon::parse($secondBreak->break_in_at)->format('H:i') : '';
    $b2_out = $secondBreak && $secondBreak->break_out_at
              ? Carbon::parse($secondBreak->break_out_at)->format('H:i') : '';
@endphp

@extends('layouts.app')

@section('title', '勤怠詳細（管理者）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_detail.css') }}">
@endpush

@section('content')
    <div class="adm-attd-dtl">
        <div class="adm-attd-dtl__inner">

            {{-- タイトル --}}
            <h1 class="adm-attd-dtl__title">勤怠詳細</h1>

            {{-- フォーム全体 --}}
            <form method="POST"
                  action="{{ route('admin.attendance.update', ['attendance' => $attendance->id]) }}"
                  class="adm-attd-dtl__form">
                @csrf
                @method('PUT')

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

                            {{-- ★ テスト用：ISO形式の日付（例: 2024-01-10）も埋め込んでおく --}}
                            <span class="adm-attd-dtl__date-iso" style="display:none;">
                                {{ $workDate->format('Y-m-d') }}
                            </span>
                        </div>
                    </div>

                    {{-- 出勤・退勤（入力） --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">出勤・退勤</div>
                        <div class="adm-attd-dtl__td">
                            <div class="adm-attd-dtl__time-range">
                                <input type="time"
                                       name="clock_in"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('clock_in', $ci) }}">
                                <span class="adm-attd-dtl__tilde">〜</span>
                                <input type="time"
                                       name="clock_out"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('clock_out', $co) }}">
                            </div>

                            @error('clock_out')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 休憩1 --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">休憩</div>
                        <div class="adm-attd-dtl__td">
                            <div class="adm-attd-dtl__time-range">
                                <input type="time"
                                       name="break1_in"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('break1_in', $b1_in) }}">
                                <span class="adm-attd-dtl__tilde">〜</span>
                                <input type="time"
                                       name="break1_out"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('break1_out', $b1_out) }}">
                            </div>
                            @error('break1_in')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                            @error('break1_out')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 休憩2 --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">休憩2</div>
                        <div class="adm-attd-dtl__td">
                            <div class="adm-attd-dtl__time-range">
                                <input type="time"
                                       name="break2_in"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('break2_in', $b2_in) }}">
                                <span class="adm-attd-dtl__tilde">〜</span>
                                <input type="time"
                                       name="break2_out"
                                       class="adm-attd-dtl__time-input"
                                       value="{{ old('break2_out', $b2_out) }}">
                            </div>
                            @error('break2_in')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                            @error('break2_out')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 備考 --}}
                    <div class="adm-attd-dtl__row adm-attd-dtl__row--note">
                        <div class="adm-attd-dtl__th">備考</div>
                        <div class="adm-attd-dtl__td">
                            <input type="text"
                                   name="note"
                                   class="adm-attd-dtl__note-input"
                                   value="{{ old('note', $attendance->note) }}"
                                   placeholder="">
                            @error('note')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>{{-- /.adm-attd-dtl__card --}}

                {{-- ステータス（hidden のまま） --}}
                <input type="hidden" name="status" value="{{ old('status', $attendance->status) }}">

                {{-- ボタン行：修正 --}}
                <div class="adm-attd-dtl__actions">
                    <button type="submit" class="adm-attd-dtl__submit">
                        修正
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection