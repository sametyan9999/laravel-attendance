{{-- resources/views/admin/request/index.blade.php --}}
@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('title', '申請一覧（管理者）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/request_index.css') }}">
@endpush

@section('content')
<div class="adm-req">
  <div class="adm-req__inner">

    {{-- タイトル --}}
    <h1 class="adm-req__title">申請一覧</h1>

    {{-- タブ（承認待ち / 承認済み） --}}
    @php $tab = $tab ?? 'pending'; @endphp
    <div class="adm-req__tabs" role="tablist">
      <a href="{{ route('admin.request.index', ['tab' => 'pending']) }}"
         class="adm-req__tab {{ $tab === 'pending' ? 'is-active' : '' }}">
        承認待ち
      </a>
      <a href="{{ route('admin.request.index', ['tab' => 'approved']) }}"
         class="adm-req__tab {{ $tab === 'approved' ? 'is-active' : '' }}">
        承認済み
      </a>
    </div>

    {{-- 一覧カード --}}
    <div class="adm-req__card">
      <table class="adm-req__table">
        <thead>
          <tr>
            <th class="col-status">状態</th>
            <th class="col-name">名前</th>
            <th class="col-target">対象日時</th>
            <th class="col-reason">申請理由</th>
            <th class="col-date">申請日時</th>
            <th class="col-detail">詳細</th>
          </tr>
        </thead>
        <tbody>
        @forelse($requests as $req)
          @php
              // 状態ラベル
              $statusLabel = match ($req->status) {
                  'approved' => '承認済み',
                  'rejected' => '却下',
                  default    => '承認待ち',
              };

              // 対象日時（勤怠の日付＋出勤時刻など、持っている情報に合わせて整形）
              $workDate = optional($req->attendance)->work_date;
              if ($workDate instanceof \Carbon\Carbon) {
                  $workDateText = $workDate->format('Y/m/d');
              } else {
                  $workDateText = $workDate ?: '';
              }

              // 申請日時
              $createdAt = $req->created_at instanceof \Carbon\Carbon
                  ? $req->created_at->format('Y/m/d H:i')
                  : ($req->created_at ?? '');
          @endphp
          <tr>
            {{-- 状態 --}}
            <td>{{ $statusLabel }}</td>

            {{-- 申請者名（StampCorrectionRequest::requester 関連） --}}
            <td>{{ optional($req->requester)->name ?? '' }}</td>

            {{-- 対象日時 --}}
            <td>{{ $workDateText }}</td>

            {{-- 申請理由（requested_note を想定） --}}
            <td class="adm-req__cell-reason">
              {{ $req->requested_note ?? '' }}
            </td>

            {{-- 申請日時 --}}
            <td>{{ $createdAt }}</td>

            {{-- 詳細リンク --}}
            <td>
              <a href="{{ route('admin.request.show', ['stamp_request' => $req->id]) }}"
                 class="adm-req__detail-link">
                詳細
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="adm-req__empty">
              申請はありません
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>

      {{-- ページネーション（必要なら） --}}
      <div class="adm-req__pagination">
        {{ $requests->withQueryString()->links() }}
      </div>
    </div>

  </div>
</div>
@endsection