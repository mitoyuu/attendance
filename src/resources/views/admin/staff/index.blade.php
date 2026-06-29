@extends('layouts.default')
<!-- 管理者 スタッフ一覧 -->

<!-- タイトル -->
<!-- @section('title','管理者勤怠一覧') -->

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/staff/index.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

@include('components.admin_header')
<h1 class="title">スタッフ一覧</h1>
<div class="admin__attendance-list">
    <table>
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <!-- 名前 -->
                <td>{{ $user->name }}</td>
                <!-- メールアドレス -->
                <td>{{ $user->email }}</td>
                <!-- 月次勤怠 -->
                <!-- 詳細 -->
                <td>
                    <a href="/admin/attendance/staff/{{ $user->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection