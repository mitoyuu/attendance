@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/index.css') }}">
@endsection

@section('content')

@include('components.header')

<div class="attendance">

    <h1 class="page__title">勤怠一覧</h1>
        <div class="attendance__month">
            <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}">
                ← 前月
            </a>

            <span class="calendar-wrapper">
                <label class="calendar-trigger">
                    <img src="{{ asset('img/カレンダーアイコン8.png') }}"
                        class="icon-monthly">

                    <input
                        type="month"
                        class="calendar-only"
                        value="{{ $month }}">
                </label>

                <span class="month-text">
                    {{ \Carbon\Carbon::parse($month)->format('Y/m') }}
                </span>
            </span>

            <a href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}">
                翌月 →
            </a>
        </div>
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
                $attendance = $attendances[$date->format('Y-m-d')] ?? null;
                @endphp

                <tr>

                    <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>

                    <td>
                        {{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
                    </td>

                    <td>
                        {{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
                    </td>

                    <td>
                        {{ $attendance ? $attendance->break_total_format : '' }}
                    </td>

                    <td>
                        {{ $attendance ? $attendance->work_total_format : '' }}
                    </td>

                    <td>
                        @if($attendance)
                            <a href="/attendance/detail/{{ $attendance->id }}">
                                詳細
                            </a>
                        @else
                            <a href="/attendance/prepare/{{ $date }}">
                                詳細
                            </a>
                        @endif
                    </td>

                </tr>
                @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection