@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
    <h1 class="gt-title">ログイン</h1>

    <form method="POST" action="{{ route('login') }}" class="gt-form">
        @csrf

        <label class="gt-label" for="email">メールアドレス</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            class="gt-input"
            autofocus
        >
        @error('email')
            <p class="gt-error">{{ $message }}</p>
        @enderror

        <label class="gt-label" for="password">パスワード</label>
        <input
            id="password"
            type="password"
            name="password"
            class="gt-input"
        >
        @error('password')
            <p class="gt-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="gt-btn">ログインする</button>

        <p class="gt-link-row">
            <a class="gt-link" href="{{ route('register') }}">会員登録はこちら</a>
        </p>
    </form>
@endsection