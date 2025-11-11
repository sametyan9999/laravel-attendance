@extends('layouts.app')

@section('title', '勤怠一覧')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endpush

@section('content')
<div class="al-wrapper">

  <h1 class="al-title">勤怠一覧</h1>

  {{-- 月切替バー --}}
  <div class="al-monthbar">
    <a class="al-monthbtn" href="{{ route('attendance.list', ['month'=>$prevMonth]) }}">前月</a>
    <div class="al-monthnow">
      <span class="al-monthnow__icon">📅</span>
      <span class="al-monthnow__text">{{ \Carbon\Carbon::parse($month.'-01')->format('Y/m') }}</span>
    </div>
    <a class="al-monthbtn" href="{{ route('attendance.list', ['month'=>$nextMonth]) }}">翌月</a>
  </div>

  {{-- 一覧テーブル --}}
  <div class="al-tablewrap">
    <table class="al-table">
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th class="al-col-detail">詳細</th>
        </tr>
      </thead>
      <tbody>
        @foreach($days as $d)
          @php
            $att = $d['attendance'];
          @endphp
          <tr>
            <td class="al-col-date">
              {{ $d['date']->format('m/d') }}
              <span class="al-dow">（{{ ['日','月','火','水','木','金','土'][$d['date']->dayOfWeek] }}）</span>
            </td>
            <td>{{ $att? $att->clock_in_at?->format('H:i') ?? '—' : '—' }}</td>
            <td>{{ $att? $att->clock_out_at?->format('H:i') ?? '—' : '—' }}</td>
            <td>{{ $att? $att->break_hm ?? '—' : '—' }}</td>
            <td>{{ $att? $att->work_hm ?? '—' : '—' }}</td>
            <td class="al-col-detail">
              @if($att)
                <a class="al-detail" href="{{ route('attendance.detail', $att) }}">詳細</a>
              @else
                <span class="al-detail is-disabled">詳細</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
@endsection