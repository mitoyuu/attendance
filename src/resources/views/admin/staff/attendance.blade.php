@extends('layouts.default')

@section('title','スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/staff/attendance.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="attendance">

    {{-- タイトル --}}
    <h1 class="page__title">
        {{ $user->name }}さんの勤怠
    </h1>

    {{-- 月切替 --}}
    <div class="attendance__month">

        <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}">
            ← 前月
        </a>

        <span class="calendar-wrapper">

            <label class="calendar-trigger">
                <img
                    src="{{ asset('img/カレンダーアイコン8.png') }}"
                    class="icon-monthly"
                >

                <input
                    type="month"
                    class="calendar-only"
                    value="{{ $month }}"
                >
            </label>

            <span class="month-text">
                {{ \Carbon\Carbon::parse($month)->format('Y/m') }}
            </span>

        </span>

        <a href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}">
            翌月 →
        </a>

    </div>

    {{-- テーブルカード --}}
    <div class="attendance__card">

        <table class="table attendance__table">

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

                @foreach ($period as $date)

                    @php
                        $attendance =
                            $attendances[$date->format('Y-m-d')]
                            ?? null;
                    @endphp

                    <tr>

                        <td>
                            {{ $date->isoFormat('MM/DD(ddd)') }}
                        </td>

                        <td>
                            {{ $attendance?->clock_in
                            ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                            : '' }}
                        </td>

                        <td>
                            {{ $attendance?->clock_out
                            ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                            : '' }}
                        </td>

                        <td>
                            {{ $attendance
                            ? $attendance->break_total_format
                            : '' }}
                        </td>

                        <td>
                            {{ $attendance
                            ? $attendance->work_total_format
                            : '' }}
                        </td>

                        <td>

                            @if($attendance)

                                <a href="/admin/attendance/{{ $attendance->id }}">
                                    詳細
                                </a>

                            @else

                                <a href="/admin/attendance/prepare/{{ $user->id }}/{{ $date->format('Y-m-d') }}">
                                    詳細
                                </a>

                            @endif

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    {{-- CSV --}}
    <div class="csv-export-wrap">

        <a
            href="{{ route('attendance.csv',['id'=>$user->id,'month'=>$month]) }}"
            class="btn csv-btn"
        >
            CSV出力
        </a>

    </div>

</div>

@endsection