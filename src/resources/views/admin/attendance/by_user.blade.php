{{-- resources/views/admin/attendance/by_user.blade.php --}}
@php
    use Carbon\Carbon;
    use Carbon\CarbonImmutable;

    /** @var string $month (例: "2023-06") */
    $base = CarbonImmutable::parse($month . '-01');

    $prevMonth = $base->subMonth()->format('Y-m');
    $nextMonth = $base->addMonth()->format('Y-m');
@endphp

@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@push('styles')
  {{-- 勤怠一覧と同じテイストで良ければ同じCSSを使い回し --}}
  <link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endpush

@section('content')
  <div class="adm-att">
    <div class="adm-att__inner">

      {{-- タイトル：〇〇さんの2023年6月の勤怠 --}}
      <h1 class="adm-att__title">
        {{ $user->name }} さんの {{ $base->format('Y年n月') }}の勤怠
      </h1>

      {{-- 月切り替えバー --}}
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

      {{-- 一覧カード --}}
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
              // 勤怠一覧と同じフォーマッタ
              $fmtTime = function ($v) {
                  if (!$v) return '—';
                  return Carbon::parse($v)->format('H:i');
              };
              $fmtMinutes = function ($attendance) {
                  if (!$attendance) return '—';

                  $breakMin = 0;
                  foreach ($attendance->breaks ?? [] as $b) {
                      if ($b->break_in_at && $b->break_out_at) {
                          $in  = Carbon::parse($b->break_in_at);
                          $out = Carbon::parse($b->break_out_at);
                          if ($out->greaterThan($in)) {
                              $breakMin += $out->diffInMinutes($in);
                          }
                      }
                  }

                  if ($attendance->clock_in_at && $attendance->clock_out_at) {
                      $ci = Carbon::parse($attendance->clock_in_at);
                      $co = Carbon::parse($attendance->clock_out_at);
                      $total = $co->diffInMinutes($ci) - $breakMin;
                      if ($total < 0) $total = 0;
                      $h = intdiv($total, 60);
                      $m = $total % 60;
                      return sprintf('%d:%02d', $h, $m);
                  }

                  return '—';
              };
          @endphp

          @forelse($rows as $att)
            <tr>
              <td>
                {{ Carbon::parse($att->work_date)->format('n月j日') }}
              </td>
              <td>{{ $fmtTime($att->clock_in_at) }}</td>
              <td>{{ $fmtTime($att->clock_out_at) }}</td>
              <td>
                {{-- 合計休憩時間だけざっくり表示（なくてもOKなら消してOK） --}}
                {{ $fmtMinutes($att) === '—' ? '—' : '' }}
              </td>
              <td>{{ $fmtMinutes($att) }}</td>
              <td>
                <a href="{{ route('admin.attendance.detail', ['attendance' => $att->id]) }}"
                   class="adm-att__detail-link">
                  詳細
                </a>
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

    </div>
  </div>
@endsection