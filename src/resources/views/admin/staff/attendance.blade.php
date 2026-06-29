@extends('layouts.default')
<!-- 管理者 スタッフ別勤怠 -->

<!-- タイトル -->
<!-- @section('title','勤怠一覧') -->

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/staff/attendance.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

@include('components.admin_header')
<h1 class="title">{{ $user->name }}さんの勤怠
</h1>
<div class="attendance_list">
    <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}">
        ← 前月
    </a>
    <span>
        <img src="{{ asset('img/カレンダーアイコン8 (1).png') }}" alt="カレンダー">
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
                <!-- 勤怠モデルに新しいBreakTotalFormatアクセサを作成したので上記記入でOK
                <td>{{ $attendance
                        ? intdiv($attendance->break_total, 3600) . ':' .
                        str_pad(intdiv($attendance->break_total % 3600, 60), 2, '0', STR_PAD_LEFT )
                        : ''
                    }}
                </td> -->

                <!-- 合計 -->
                <td>{{ $attendance ? $attendance->work_total_format : '' }}</td>

                <!-- 詳細 -->
                <td>@if($attendance)
                    <a href="/admin/attendance/{{ $attendance->id }}">詳細</a>
                    @else
                    <a href="/admin/attendance/prepare/{{ $user->id }}/{{ $date->format('Y-m-d') }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{-- CSV出力 --}}
    <div class="csv-export-wrap">
        <a href="{{ route('attendance.csv', ['id' => $user->id, 'month' => $month]) }}" class="btn btn-success">
            CSV出力
        </a>
    </div>
@endsection