@extends('layouts.default')

<!-- タイトル -->
@section('title','管理者勤怠一覧')

@section('css')
{{-- 管理者勤怠一覧 --}}

<link rel="stylesheet" href="{{ asset('/css/admin/attendance/index.css')  }}">
@endsection

@section('content')
{{-- 本体 --}}

@include('components.admin_header')

<div class="attendance">

    <h1 class="page__title">{{ \Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h1>

        <div class="attendance__date-card">
            <a href="?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}" class="date-nav">
                ← 前日
            </a>
            <span class="calendar-wrapper">
                <label class="calendar-trigger">
                    <img src="{{ asset('img/カレンダーアイコン8.png') }}" alt="カレンダー" class="icon-monthly">
                        <input type="date" class="calendar-only" value="{{ $date }}">
                </label>
            <span class="month-text">
                {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}
            </span>
            </span>
                <a href="?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}" class="date-nav">
                    翌日 →
                </a>
        </div>

    <div class="attendance__card">
        <table class="table attendance__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
            <tr>
                <!-- 名前 -->
                <td>{{ $attendance->user->name }}</td>
                <!-- 出勤 -->
                <td>{{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <!-- 退勤 -->
                <td>{{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                <!-- 休憩 -->
                <td>{{ $attendance ? $attendance->break_total_format : '' }}</td>
                <!-- 合計 -->
                <td>{{ $attendance ? $attendance->work_total_format : '' }}</td>
                <!-- 詳細 -->
                <td class="cell-detail">
                    @if($attendance)
                    <a href="/admin/attendance/{{ $attendance->id }}">詳細</a>
                    @endif
                </td>
            </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection