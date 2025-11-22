@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endpush

@section('content')
    <div class="adm-att">
        <div class="adm-att__inner">

            <h1 class="adm-att__title">
                {{ $date->format('Y年n月j日') }}の勤怠
            </h1>

            <div class="adm-att__toolbar" role="group" aria-label="日付切替">
                <a class="adm-att__nav adm-att__nav--prev"
                   href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}">
                    <span class="adm-att__nav-icon">←</span>前日
                </a>

                <div class="adm-att__nav adm-att__nav--current">
                    <span class="adm-att__nav-icon adm-att__nav-icon--calendar" aria-hidden="true">📅</span>
                    <time datetime="{{ $date->format('Y-m-d') }}">{{ $date->format('Y/m/d') }}</time>
                </div>

                <a class="adm-att__nav adm-att__nav--next"
                   href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}">
                    翌日<span class="adm-att__nav-icon">→</span>
                </a>
            </div>

            <div class="adm-att__card">
                <table class="adm-att__table">
                    <thead>
                        <tr>
                            <th>名前</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $user = $row['user'];
                                $att = $row['attendance'];
                                $ci = $row['clock_in'];
                                $co = $row['clock_out'];
                                $bm = $row['break_minutes'];
                                $tm = $row['total_minutes'];

                                $fmtTime = function ($v) {
                                    if (!$v) return '—';
                                    return Carbon::parse($v)->format('H:i');
                                };

                                $fmtMinutes = function ($min) {
                                    if (!is_numeric($min)) return '—';
                                    $h = intdiv((int)$min, 60);
                                    $m = (int)$min % 60;
                                    return sprintf('%d:%02d', $h, $m);
                                };
                            @endphp

                            <tr>
                                <td class="adm-att__cell-name">{{ $user->name }}</td>
                                <td>{{ $fmtTime($ci) }}</td>
                                <td>{{ $fmtTime($co) }}</td>
                                <td>{{ $fmtMinutes($bm) }}</td>
                                <td>{{ $fmtMinutes($tm) }}</td>
                                <td>
                                    @if($att)
                                        <a href="{{ route('admin.attendance.detail', ['attendance' => $att->id]) }}"
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
                                <td colspan="6" class="adm-att__empty">データがありません</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>
@endsection