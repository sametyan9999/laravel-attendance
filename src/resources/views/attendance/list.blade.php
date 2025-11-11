{{-- resources/views/attendance/list.blade.php --}}
@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')
  <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}"/>

  @php
    use Carbon\Carbon;
    use Carbon\CarbonImmutable;
    use Carbon\CarbonInterface;

    /* ---------- ユーティリティ ---------- */
    $toCarbon = function ($v, $default = null) {
        try {
            if ($v instanceof CarbonInterface) return CarbonImmutable::instance($v);
            if (is_array($v)) {
                $cand = $v['date'] ?? $v['day'] ?? $v['value'] ?? reset($v);
                if ($cand instanceof CarbonInterface) return CarbonImmutable::instance($cand);
                if (is_string($cand)) return CarbonImmutable::parse($cand);
                return $default;
            }
            if (is_string($v)) return CarbonImmutable::parse($v);
        } catch (\Throwable $e) {}
        return $default;
    };

    $fmtTime = function ($v) {
        if ($v === null || $v === '') return '—';
        try {
            if ($v instanceof CarbonInterface) return $v->format('H:i');
            if (is_string($v)) return Carbon::parse($v)->format('H:i');
        } catch (\Throwable $e) {
            return is_string($v) ? $v : '—';
        }
        return '—';
    };

    $fmtMinutes = function ($min) {
        if (!is_numeric($min)) return '—';
        $h = intdiv((int)$min, 60);
        $m = (int)$min % 60;
        return sprintf('%d:%02d', $h, $m);
    };

    /* ---------- 月・前月・翌月 正規化 ---------- */
    $rawMonth = $month ?? request('month');
    if ($rawMonth instanceof CarbonInterface) {
        $monthObj = CarbonImmutable::instance($rawMonth)->startOfMonth();
    } elseif (is_string($rawMonth) && preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
        $monthObj = CarbonImmutable::parse($rawMonth.'-01')->startOfMonth();
    } elseif (is_array($rawMonth)) {
        $monthObj = ($toCarbon($rawMonth, CarbonImmutable::now()))->startOfMonth();
    } else {
        $monthObj = CarbonImmutable::now()->startOfMonth();
    }

    $prevObj = ($prevMonth ?? null) ? $toCarbon($prevMonth, $monthObj->subMonth())->startOfMonth() : $monthObj->subMonth();
    $nextObj = ($nextMonth ?? null) ? $toCarbon($nextMonth, $monthObj->addMonth())->startOfMonth() : $monthObj->addMonth();

    /* ---------- 日付配列 正規化 ---------- */
    $daysList = [];
    if (!empty($days) && is_iterable($days)) {
        foreach ($days as $d) {
            $c = $toCarbon($d);
            if ($c) $daysList[] = CarbonImmutable::instance($c);
        }
    }
    if (empty($daysList)) {
        for ($i = 1; $i <= $monthObj->daysInMonth; $i++) {
            $daysList[] = $monthObj->day($i);
        }
    }

    /* ---------- レコード配列の既定 ---------- */
    $records = is_array($records ?? null) ? $records : [];
  @endphp

  <div class="attdlist-wrap">
    <div class="attdlist-inner">

      {{-- タイトル --}}
      <h1 class="attdlist-title">勤怠一覧</h1>

      {{-- 月切り替えツールバー --}}
      <div class="attdlist-toolbar" role="group" aria-label="月切替">
        <a class="tool-btn" href="{{ route('attendance.list', ['month' => $prevObj->format('Y-m')]) }}">
          <span class="arrow-left">←</span> 前月
        </a>

        <div class="tool-current">
          <span class="icon-calendar" aria-hidden="true">📅</span>
          <time datetime="{{ $monthObj->format('Y-m') }}">{{ $monthObj->format('Y/m') }}</time>
        </div>

        <a class="tool-btn" href="{{ route('attendance.list', ['month' => $nextObj->format('Y-m')]) }}">
          翌月 <span class="arrow-right">→</span>
        </a>
      </div>

      {{-- 一覧カード --}}
      <div class="attdlist-card">
        <table class="attdlist-table">
          <thead>
            <tr>
              <th scope="col" class="col-date">日付</th>
              <th scope="col">出勤</th>
              <th scope="col">退勤</th>
              <th scope="col">休憩</th>
              <th scope="col">合計</th>
              <th scope="col" class="col-detail">詳細</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($daysList as $day)
            @php
              $key = $day->format('Y-m-d');
              $r   = $records[$key] ?? null;

              // 配列/オブジェクト両対応で値を取得
              $ci = is_array($r) ? ($r['clock_in'] ?? null)      : ($r->clock_in      ?? null);
              $co = is_array($r) ? ($r['clock_out'] ?? null)     : ($r->clock_out     ?? null);
              $bm = is_array($r) ? ($r['break_minutes'] ?? null) : ($r->break_minutes ?? null);
              $tm = is_array($r) ? ($r['total_minutes'] ?? null) : ($r->total_minutes ?? null);
              $id = is_array($r) ? ($r['id'] ?? null)            : ($r->id            ?? null);

              $clockIn  = $fmtTime($ci);
              $clockOut = $fmtTime($co);
              $breakStr = $fmtMinutes($bm);
              $totalStr = $fmtMinutes($tm);
              $dow = ['日','月','火','水','木','金','土'][$day->dayOfWeek];
            @endphp
            <tr>
              <th scope="row" class="col-date">
                {{ $day->format('m/d') }}（{{ $dow }}）
              </th>
              <td>{{ $clockIn }}</td>
              <td>{{ $clockOut }}</td>
              <td>{{ $breakStr }}</td>
              <td>{{ $totalStr }}</td>
              <td class="col-detail">
                @if($id)
                  <a class="link-detail" href="{{ route('attendance.detail', ['attendance' => $id]) }}">詳細</a>
                @else
                  <span class="link-detail disabled" aria-disabled="true">詳細</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="empty">データがありません</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection