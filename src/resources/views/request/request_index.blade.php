{{-- resources/views/request/request_index.blade.php --}}
@extends('layouts.app')

@section('title','申請一覧')

@section('content')
  <style>
    .rq-wrap{max-width:980px;margin:48px auto 96px;padding:0 16px}
    .rq-title{display:flex;align-items:center;font-weight:800;font-size:22px;letter-spacing:.04em;margin:0 0 24px}
    .rq-title::before{content:"";display:inline-block;width:6px;height:22px;border-radius:3px;background:#111;margin-right:10px}

    .rq-tabs{display:flex;gap:32px;border-bottom:1px solid #E5E7EB;margin-bottom:18px;padding:0 4px}
    .rq-tab{position:relative;display:inline-block;padding:10px 2px 12px;color:#9CA3AF;font-weight:700;text-decoration:none}
    .rq-tab.is-active{color:#111}
    .rq-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:3px;background:#111;border-radius:3px}

    .rq-card{background:#fff;border:1px solid #EEE;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.03);overflow:hidden}
    .rq-table{width:100%;border-collapse:separate;border-spacing:0}
    .rq-table thead th{background:#F9FAFB;color:#374151;font-weight:700}
    .rq-table th,.rq-table td{padding:16px 18px;border-bottom:1px solid #EEE;vertical-align:middle}
    .rq-table th:nth-child(1){width:110px}
    .rq-table th:nth-child(2){width:120px}
    .rq-table th:nth-child(3){width:150px}
    .rq-table th:nth-child(4){min-width:200px}
    .rq-table th:nth-child(5){width:150px}
    .rq-table th:nth-child(6){width:80px}
    .rq-table tbody tr:last-child td{border-bottom:none}

    .rq-badge{display:inline-block;min-width:86px;text-align:center;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:800}
    .rq-badge--pending{background:#FFF7ED;color:#9A3412;border:1px solid #FDBA74}
    .rq-badge--approved{background:#ECFDF5;color:#065F46;border:1px solid #86EFAC}

    .rq-detail{display:inline-block;padding:6px 12px;border:1px solid #111;border-radius:10px;text-decoration:none;color:#111;font-weight:700}
    .rq-empty{padding:32px;color:#6B7280;text-align:center}
    .rq-name{font-weight:700}

    @media (max-width:720px){
      .rq-table thead{display:none}
      .rq-table tbody tr{display:grid;grid-template-columns:1fr 1fr;gap:8px;border-bottom:1px solid #EEE;padding:12px 16px}
      .rq-table td{border:none;padding:6px 0}
      .rq-table td[data-th]::before{content:attr(data-th) "：";display:inline-block;color:#6B7280;margin-right:4px}
    }
  </style>

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
              $workDate = optional($r->attendance)->work_date ? \Carbon\Carbon::parse($r->attendance->work_date)->format('Y/m/d') : '—';
              $created  = \Carbon\Carbon::parse($r->created_at)->format('Y/m/d');
              $detailHref = optional($r->attendance)->id ? route('attendance.detail', ['attendance'=>$r->attendance->id]) : null;
            @endphp
            <tr>
              <td data-th="状態"><span class="rq-badge rq-badge--pending">承認待ち</span></td>
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
              $workDate = optional($r->attendance)->work_date ? \Carbon\Carbon::parse($r->attendance->work_date)->format('Y/m/d') : '—';
              $created  = \Carbon\Carbon::parse($r->created_at)->format('Y/m/d');
              $detailHref = optional($r->attendance)->id ? route('attendance.detail', ['attendance'=>$r->attendance->id]) : null;
            @endphp
            <tr>
              <td data-th="状態"><span class="rq-badge rq-badge--approved">承認済み</span></td>
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
    (function(){
      const $tp = document.getElementById('tab-pending');
      const $ta = document.getElementById('tab-approved');
      const $pp = document.getElementById('pane-pending');
      const $pa = document.getElementById('pane-approved');

      function activate(which){
        if(which==='approved'){
          $ta.classList.add('is-active'); $tp.classList.remove('is-active');
          $pa.style.display='block'; $pp.style.display='none';
          location.hash='#approved';
        }else{
          $tp.classList.add('is-active'); $ta.classList.remove('is-active');
          $pp.style.display='block'; $pa.style.display='none';
          location.hash='#pending';
        }
      }
      $tp.addEventListener('click', e=>{ e.preventDefault(); activate('pending'); });
      $ta.addEventListener('click', e=>{ e.preventDefault(); activate('approved'); });

      // 初期表示
      if(location.hash==='#approved'){ activate('approved'); }
    })();
  </script>
@endsection