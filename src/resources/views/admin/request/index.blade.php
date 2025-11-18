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
    @php
        // コントローラから渡ってくる $tab を信頼しつつ、保険でデフォルト pending
        $tab = $tab ?? 'pending';
    @endphp
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
              /** @var \App\Models\StampCorrectionRequest $req */

              // ▼ 状態ラベルは「どのタブか」で決める
              $statusLabel = $tab === 'approved' ? '承認済み' : '承認待ち';

              // 対象日時
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
            <td class="col-status">{{ $statusLabel }}</td>

            {{-- 申請者名 --}}
            <td class="col-name">{{ optional($req->requester)->name ?? '' }}</td>

            {{-- 対象日時 --}}
            <td class="col-target">{{ $workDateText }}</td>

            {{-- 申請理由 --}}
            <td class="adm-req__cell-reason col-reason">
              {{ $req->requested_note ?? '' }}
            </td>

            {{-- 申請日時 --}}
            <td class="col-date">{{ $createdAt }}</td>

            {{-- 詳細リンク --}}
            <td class="col-detail">
              <a href="{{ route('admin.request.show', ['attendance_correct_request_id' => $req->id]) }}"
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

      {{-- ページネーション --}}
      <div class="adm-req__pagination">
        {{ $requests->withQueryString()->links() }}
      </div>
    </div>

  </div>
</div>
@endsection