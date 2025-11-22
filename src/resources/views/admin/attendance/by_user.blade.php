@php
    use Carbon\Carbon;
    use Carbon\CarbonImmutable;

    $base = CarbonImmutable::parse($month . '-01');
    $prevMonth = $base->subMonth()->format('Y-m');
    $nextMonth = $base->addMonth()->format('Y-m');
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endpush

@section('content')
    <div class="adm-att">
        <div class="adm-att__inner">

            <h1 class="adm-att__title">
                {{ $user->name }} さんの {{ $base->format('Y年n月') }} の勤怠
            </h1>

            <div class="adm-att__toolbar" role="group" aria-label="月切替">
                <a class="adm-att__nav adm-att__nav--prev"
                   href="{{ route('admin.attendance.by_user', ['user' => $user->id, 'month' => $prevMonth]) }}">
                    <span class="adm-att__nav-icon">←</span>
                    前月
                </a>

                <div class="adm-att__nav adm-att__nav--current">
                    <span class="adm-att__nav-icon adm-att__nav-icon--calendar" aria-hidden="true">📅</span>
                    <span>{{ $base->format('Y/m') }}</span>
                </div>

                <a class="adm-att__nav adm-att__nav--next"
                   href="{{ route('admin.attendance.by_user', ['user' => $user->id, 'month' => $nextMonth]) }}">
                    翌月
                    <span class="adm-att__nav-icon">→</span>
                </a>
            </div>

            <div class="adm-att__card">
                <table class="adm-att__table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $fmtTime = function ($v) {
                                if (!$v) return '—';
                                return Carbon::parse($v)->format('H:i');
                            };

                            $fmtHM = function ($min) {
                                if (!is_numeric($min)) return '—';
                                $h = intdiv((int)$min, 60);
                                $m = (int)$min % 60;
                                return sprintf('%d:%02d', $h, $m);
                            };
                        @endphp

                        @forelse($rows as $row)
                            @php
                                $date = $row['date'];
                                $attendance = $row['attendance'];
                                $clockIn = $row['clock_in'];
                                $clockOut = $row['clock_out'];
                                $breakMin = $row['break_minutes'];
                                $totalMin = $row['total_minutes'];
                                $w = $weekdays[$date->dayOfWeek] ?? '';
                            @endphp

                            <tr>
                                <td>{{ $date->format('m/d') }}({{ $w }})</td>
                                <td>{{ $fmtTime($clockIn) }}</td>
                                <td>{{ $fmtTime($clockOut) }}</td>
                                <td>{{ $fmtHM($breakMin) }}</td>
                                <td>{{ $fmtHM($totalMin) }}</td>
                                <td>
                                    @if($attendance)
                                        <a href="{{ route('admin.attendance.detail', ['attendance' => $attendance->id]) }}"
                                           class="adm-att__detail-link">
                                            詳細
                                        </a>
                                    @else
                                        <span class="adm-att__detail-link adm-att__detail-link--disabled" aria-disabled="true">
                                            詳細
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="adm-att__empty">
                                    この月の勤怠データがありません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="adm-att__csv">
                <a href="{{ route('admin.attendance.by_user_csv', ['user' => $user->id, 'month' => $base->format('Y-m')]) }}"
                   class="adm-att__csv-btn">
                    CSV出力
                </a>
            </div>

        </div>
    </div>
@endsection