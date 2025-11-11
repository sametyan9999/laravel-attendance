@extends('layouts.app')

@section('title','会員登録')

@section('content')
  <h1 class="gt-title">会員登録</h1>

  <form method="POST" action="{{ route('register') }}" class="gt-form">
    @csrf

    <label class="gt-label" for="name">名前</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" class="gt-input">
    @error('name')<p class="gt-error">{{ $message }}</p>@enderror

    <label class="gt-label" for="email">メールアドレス</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" class="gt-input">
    @error('email')<p class="gt-error">{{ $message }}</p>@enderror

    <label class="gt-label" for="password">パスワード</label>
    <input id="password" type="password" name="password" class="gt-input">
    @error('password')<p class="gt-error">{{ $message }}</p>@enderror

    <label class="gt-label" for="password_confirmation">パスワード確認</label>
    <input id="password_confirmation" type="password" name="password_confirmation" class="gt-input">

    <button type="submit" class="gt-btn">登録する</button>

    <p class="gt-link-row">
      <a class="gt-link" href="{{ route('login') }}">ログインはこちら</a>
    </p>
  </form>
@endsection