@extends('layouts.app')

@section('title', '申請一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/request/index.css') }}">
@endpush

@section('content')
    <div class="rq-wrap">
        <h1 class="rq-title">申請一覧</h1>

        {{-- タブ --}}
        <nav class="rq-tabs" aria-label="申請タブ">
            <a href="#pending" class="rq-tab is-active" id="tab-pending">承認待ち</a>
            <a href="#approved" class="rq-tab" id="tab-approved">承認済み</a>
        </nav>

        {{-- 承認待ち --}}
        <div class="rq-card" id="pane-pending">
            @if($pending->count())
                <table class="rq-table">
                    <thead>
                        <tr>
                            <th>状態</th>
                            <th>名前</th>
                            <th>対象日時</th>
                            <th>申請理由</th>
                            <th>申請日時</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $r)
                            @php
                                $userName = auth()->user()->name; // 自分の一覧なので本人名でOK
                                $workDate = optional($r->attendance)->work_date
                                    ? \Carbon\Carbon::parse($r->attendance->work_date)->format('Y/m/d')
                                    : '—';
                                $created = \Carbon\Carbon::parse($r->created_at)->format('Y/m/d');
                                $detailHref = optional($r->attendance)->id
                                    ? route('attendance.detail', ['attendance' => $r->attendance->id])
                                    : null;
                            @endphp
                            <tr>
                                <td data-th="状態">
                                    <span class="rq-badge rq-badge--pending">承認待ち</span>
                                </td>
                                <td data-th="名前" class="rq-name">{{ $userName }}</td>
                                <td data-th="対象日時">{{ $workDate }}</td>
                                <td data-th="申請理由">{{ $r->requested_note ?? '—' }}</td>
                                <td data-th="申請日時">{{ $created }}</td>
                                <td data-th="詳細">
                                    @if($detailHref)
                                        <a class="rq-detail" href="{{ $detailHref }}">詳細</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="rq-empty">承認待ちの申請はありません。</p>
            @endif
        </div>

        {{-- 承認済み --}}
        <div class="rq-card" id="pane-approved" style="display:none; margin-top:16px;">
            @if($approved->count())
                <table class="rq-table">
                    <thead>
                        <tr>
                            <th>状態</th>
                            <th>名前</th>
                            <th>対象日時</th>
                            <th>申請理由</th>
                            <th>申請日時</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approved as $r)
                            @php
                                $userName = auth()->user()->name;
                                $workDate = optional($r->attendance)->work_date
                                    ? \Carbon\Carbon::parse($r->attendance->work_date)->format('Y/m/d')
                                    : '—';
                                $created = \Carbon\Carbon::parse($r->created_at)->format('Y/m/d');
                                $detailHref = optional($r->attendance)->id
                                    ? route('attendance.detail', ['attendance' => $r->attendance->id])
                                    : null;
                            @endphp
                            <tr>
                                <td data-th="状態">
                                    <span class="rq-badge rq-badge--approved">承認済み</span>
                                </td>
                                <td data-th="名前" class="rq-name">{{ $userName }}</td>
                                <td data-th="対象日時">{{ $workDate }}</td>
                                <td data-th="申請理由">{{ $r->requested_note ?? '—' }}</td>
                                <td data-th="申請日時">{{ $created }}</td>
                                <td data-th="詳細">
                                    @if($detailHref)
                                        <a class="rq-detail" href="{{ $detailHref }}">詳細</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="rq-empty">承認済みの申請はありません。</p>
            @endif
        </div>
    </div>

    <script>
        // シンプルなタブ切替（URLハッシュで保持）
        (function () {
            const $tp = document.getElementById('tab-pending');
            const $ta = document.getElementById('tab-approved');
            const $pp = document.getElementById('pane-pending');
            const $pa = document.getElementById('pane-approved');

            function activate(which) {
                if (which === 'approved') {
                    $ta.classList.add('is-active');
                    $tp.classList.remove('is-active');
                    $pa.style.display = 'block';
                    $pp.style.display = 'none';
                    location.hash = '#approved';
                } else {
                    $tp.classList.add('is-active');
                    $ta.classList.remove('is-active');
                    $pp.style.display = 'block';
                    $pa.style.display = 'none';
                    location.hash = '#pending';
                }
            }

            $tp.addEventListener('click', (e) => {
                e.preventDefault();
                activate('pending');
            });

            $ta.addEventListener('click', (e) => {
                e.preventDefault();
                activate('approved');
            });

            // 初期表示
            if (location.hash === '#approved') {
                activate('approved');
            }
        })();
    </script>
@endsection