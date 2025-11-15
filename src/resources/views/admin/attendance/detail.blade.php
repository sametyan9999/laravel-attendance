{{-- resources/views/admin/attendance/detail.blade.php --}}
@php
    use Carbon\Carbon;

    $user     = $attendance->user;
    $workDate = Carbon::parse($attendance->work_date);

    // 休憩は開始時刻順に並べ替え
    $breaks = $attendance->breaks->sortBy('break_in_at')->values();

    $ci = $attendance->clock_in_at
        ? Carbon::parse($attendance->clock_in_at)->format('H:i')
        : '';

    $co = $attendance->clock_out_at
        ? Carbon::parse($attendance->clock_out_at)->format('H:i')
        : '';
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

            {{-- ★ フォーム全体でカード＋ボタンを囲む --}}
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
                        </div>
                    </div>

                    {{-- 出勤・退勤 --}}
                    <div class="adm-attd-dtl__row">
                        <div class="adm-attd-dtl__th">出勤・退勤</div>
                        <div class="adm-attd-dtl__td">
                            <div class="adm-attd-dtl__time-range">
                                <input type="time"
                                       name="clock_in_at"
                                       value="{{ old('clock_in_at', $ci) }}"
                                       class="adm-attd-dtl__time-input">
                                <span class="adm-attd-dtl__tilde">〜</span>
                                <input type="time"
                                       name="clock_out_at"
                                       value="{{ old('clock_out_at', $co) }}"
                                       class="adm-attd-dtl__time-input">
                            </div>
                            @error('clock_in_at')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                            @error('clock_out_at')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 休憩（件数ぶんループ表示） --}}
                    @forelse($breaks as $idx => $b)
                        @php
                            $in  = $b->break_in_at  ? Carbon::parse($b->break_in_at)->format('H:i')  : '';
                            $out = $b->break_out_at ? Carbon::parse($b->break_out_at)->format('H:i') : '';
                        @endphp
                        <div class="adm-attd-dtl__row">
                            <div class="adm-attd-dtl__th">
                                休憩{{ $idx + 1 }}
                            </div>
                            <div class="adm-attd-dtl__td">
                                <div class="adm-attd-dtl__time-range">
                                    <input type="time"
                                           name="breaks[{{ $idx }}][break_in_at]"
                                           value="{{ old("breaks.$idx.break_in_at", $in) }}"
                                           class="adm-attd-dtl__time-input">
                                    <span class="adm-attd-dtl__tilde">〜</span>
                                    <input type="time"
                                           name="breaks[{{ $idx }}][break_out_at]"
                                           value="{{ old("breaks.$idx.break_out_at", $out) }}"
                                           class="adm-attd-dtl__time-input">
                                </div>
                            </div>
                        </div>
                    @empty
                        {{-- 休憩が1件も無い場合は空行を1つ用意 --}}
                        <div class="adm-attd-dtl__row">
                            <div class="adm-attd-dtl__th">休憩1</div>
                            <div class="adm-attd-dtl__td">
                                <div class="adm-attd-dtl__time-range">
                                    <input type="time"
                                           name="breaks[0][break_in_at]"
                                           value="{{ old('breaks.0.break_in_at') }}"
                                           class="adm-attd-dtl__time-input">
                                    <span class="adm-attd-dtl__tilde">〜</span>
                                    <input type="time"
                                           name="breaks[0][break_out_at]"
                                           value="{{ old('breaks.0.break_out_at') }}"
                                           class="adm-attd-dtl__time-input">
                                </div>
                            </div>
                        </div>
                    @endforelse

                    {{-- 備考 --}}
                    <div class="adm-attd-dtl__row adm-attd-dtl__row--note">
                        <div class="adm-attd-dtl__th">備考</div>
                        <div class="adm-attd-dtl__td">
                            <textarea name="note"
                                      rows="3"
                                      class="adm-attd-dtl__note">{{ old('note', $attendance->note) }}</textarea>
                            @error('note')
                                <p class="adm-attd-dtl__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>{{-- /.adm-attd-dtl__card --}}

                {{-- ステータス（hidden） --}}
                <input type="hidden" name="status" value="{{ old('status', $attendance->status) }}">

                {{-- ★ カードの下に独立したボタン行 --}}
                <div class="adm-attd-dtl__actions">
                    <button type="submit" class="adm-attd-dtl__submit">
                        修正
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection