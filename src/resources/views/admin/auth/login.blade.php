{{-- resources/views/admin/login.blade.php --}}
@extends('layouts.app')

@section('title', '管理者ログイン')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/admin/login.css') }}?v={{ filemtime(public_path('css/admin/login.css')) }}">
@endpush

@section('content')
<div class="admin-login">
    <div class="admin-login__inner">
        <h1 class="admin-login__title">管理者ログイン</h1>

        <form class="admin-login__form" method="POST" action="{{ route('login') }}">
            @csrf

            {{-- メールアドレス --}}
            <div class="admin-login__field">
                <label for="email" class="admin-login__label">メールアドレス</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="admin-login__input"
                    autocomplete="email"
                    required
                    autofocus
                >
                @error('email')
                    <p class="admin-login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="admin-login__field">
                <label for="password" class="admin-login__label">パスワード</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="admin-login__input"
                    autocomplete="current-password"
                    required
                >
                @error('password')
                    <p class="admin-login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- 共通のバリデーションエラー（メール／パスワード以外） --}}
            @if ($errors->has('email') === false && $errors->has('password') === false && $errors->any())
                <div class="admin-login__error-block" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ボタン --}}
            <div class="admin-login__actions">
                <button type="submit" class="admin-login__button">
                    管理者ログインする
                </button>
            </div>
        </form>
    </div>
</div>
@endsection