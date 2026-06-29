@extends('layouts.default')
<!-- 勤怠一覧 -->

<!-- タイトル -->
<!-- @section('title','勤怠一覧') -->

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/index.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

@include('components.header')
<h1 class="title">勤怠一覧</h1>
<div class="attendance_list">
    <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}">
        ← 前月
    </a>
    <span>
        <img src="{{ asset('img/カレンダーアイコン8 (1).png') }}" alt="カレンダー">
        <input type="month" class="calendar-only">
        {{ \Carbon\Carbon::parse($month)->format('Y/m') }}
    </span>
    <a href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}">
        翌月 →
    </a>

    <table>
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <!-- その日の勤怠を1発で取る なければ null -->
            @foreach ($period as $date)
            @php
            $attendance = $attendances[$date->format('Y-m-d')] ?? null;
            @endphp

            <tr>
                <!-- 日付 -->
                <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                <!-- 出勤 -->
                <td>{{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <!-- 退勤 -->
                <td>{{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                <!-- 休憩 -->
                <td>{{ $attendance ? $attendance->break_total_format : '' }}</td>
                <!-- 勤怠モデルに新しいBreakTotalFormatアクセサを作成したので上記記入でOK　-->

                <!-- 合計 -->
                <td>{{ $attendance ? $attendance->work_total_format : '' }}</td>

                <!-- 詳細 -->
                <td>@if($attendance)
                    <a href="/attendance/detail/{{ $attendance->id }}">詳細</a>
                    @else
                    <a href="/attendance/prepare/{{ $date }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection