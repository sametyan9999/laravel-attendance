{{-- resources/views/admin/staff/index.blade.php --}}
@extends('layouts.app')

@section('title', 'スタッフ一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endpush

@section('content')
    <div class="adm-staff">
        <div class="adm-staff__inner">

            {{-- タイトル --}}
            <h1 class="adm-staff__title">スタッフ一覧</h1>

            {{-- 一覧カード --}}
            <div class="adm-staff__card">
                <table class="adm-staff__table">
                    <thead>
                        <tr>
                            <th class="col-name">名前</th>
                            <th class="col-mail">メールアドレス</th>
                            <th class="col-month">月次勤怠</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="col-name">{{ $user->name }}</td>
                                <td class="col-mail">{{ $user->email }}</td>
                                <td class="col-month">
                                    <a
                                        href="{{ route('admin.attendance.by_user', ['user' => $user->id]) }}"
                                        class="adm-staff__detail-link"
                                    >
                                        詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="adm-staff__empty">
                                    スタッフが登録されていません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection