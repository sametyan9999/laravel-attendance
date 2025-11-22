@extends('layouts.app')

@section('title', 'メール認証のお願い')

{{-- ★ このページでは右上ナビを非表示にする --}}
@section('hide-nav', true)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endpush

@section('content')
    <div class="verify">
        <div class="verify__inner">

            <p class="verify__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify__button-wrapper">
                <a
                    href="http://localhost:8025"
                    class="verify__button"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    認証はこちらから
                </a>
            </div>

            {{-- ★ 再送後の成功メッセージ --}}
            @if (session('status') === 'verification-link-sent')
                <p class="verify__success">
                    認証メールを再送しました。メールをご確認ください。
                </p>
            @endif

            {{-- 認証メール再送 --}}
            <form method="POST"
                action="{{ route('verification.send') }}"
                class="verify__resend-form"
            >
                @csrf
                <button type="submit" class="verify__resend-link">
                    認証メールを再送する
                </button>
            </form>

        </div>
    </div>
@endsection