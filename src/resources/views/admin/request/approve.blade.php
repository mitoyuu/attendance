@extends('layouts.default')
<!-- 管理者 申請承認 -->
<!-- タイトル -->
@section('title','修正申請承認')
@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/request/approve.css')  }}">
@endsection
@section('content')
<!-- 本体 -->
@include('components.admin_header')

<div class="attendance-approve__container">
    @if($request)
    <form action="/admin/stamp_correction_request/approve/{{$request->id}}" method="post" class="attendance__detail">
    @endif
        @csrf

        <!-- タイトル（左側に黒い縦線） -->
        <h1 class="page__title">勤怠詳細</h1>

        <!-- 各項目を包む白いカード枠 -->
        <div class="form__card">
            <div class="form__group">
                <label for="name" class="entry__name">名前</label>
                <span class="entry__value">{{ $attendance->user->name }}</span>
            </div>

            <div class="form__group">
                <label for="work_date" class="entry__name">日付</label>
                {{-- 表示用 --}}
                <span class="entry__value">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年n月j日') }}</span>
                {{-- 送信用（隠す） --}}
                <input type="hidden" name="work_date" value="{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d') }}">
            </div>

            <div class="form__group">
                <label for="requested_clock" class="entry__name">出勤・退勤</label>
                <div class="entry__value">
                    @if($request)
                    <span>{{ $request->requested_clock_in ? \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i') : '' }}</span>
                    <span class="time-separator">〜</span>
                    <span>{{ $request->requested_clock_out ? \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i') : '' }}</span>
                    @else
                    <input name="requested_clock_in" id="requested_clock" type="text" class="input" value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
                    <span class="time-separator">〜</span>
                    <input name="requested_clock_out" id="requested_clock" type="text" class="input" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                    @endif
                </div>
            </div>

            <div class="form__group-container">
                @if($request)
                    @if($breakRequests->isEmpty())
                        <div class="form__group">
                            <label class="entry__name">休憩</label>
                            <span class="entry__value"></span>
                        </div>
                    @else
                        @foreach($breakRequests as $index => $break)
                        <div class="form__group">
                            <!-- 2つ目以降は「休憩2」「休憩3」となるように変更 -->
                            <label class="entry__name">休憩{{ $index > 0 ? $index + 1 : '' }}</label>
                            <div class="entry__value">
                                <span>{{ $break->requested_break_start ? \Carbon\Carbon::parse($break->requested_break_start)->format('H:i') : '' }}</span>
                                <span class="time-separator">〜</span>
                                <span>{{ $break->requested_break_end ? \Carbon\Carbon::parse($break->requested_break_end)->format('H:i') : '' }}</span>
                            </div>
                        </div>
                        @endforeach
                    @endif
                @else
                    @foreach($attendance->breakTimes as $index => $break)
                    <div class="form__group">
                        <label class="entry__name">休憩{{ $index > 0 ? $index + 1 : '' }}</label>
                        <div class="entry__value break-row">
                            <input name="breaks[{{ $index }}][start]" type="text" class="input" value="{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}">
                            <span class="time-separator">〜</span>
                            <input name="breaks[{{ $index }}][end]" type="text" class="input" value="{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}">
                        </div>
                    </div>
                    @endforeach
                    {{-- 追加の1行 --}}
                    <div class="form__group">
                        <label class="entry__name">休憩{{ $attendance->breakTimes->count() > 0 ? $attendance->breakTimes->count() + 1 : '' }}</label>
                        <div class="entry__value break-row">
                            <input name="breaks[{{ $attendance->breakTimes->count() }}][start]" type="text" class="input" value="">
                            <span class="time-separator">〜</span>
                            <input name="breaks[{{ $attendance->breakTimes->count() }}][end]" type="text" class="input" value="">
                        </div>
                    </div>
                @endif
            </div>

            <div class="form__group no-border">
                <label for="reason" class="entry__name">備考</label>
                <span class="entry__value">
                    @if($request)
                    {{ $request->reason }}
                    @else
                    <input name="reason" id="reason" type="text" class="input" value="">
                    @endif
                </span>
            </div>
        </div>

        <!-- どの勤怠の修正なのか分かるようにattendance_idを取得するための記述 -->
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

        <!-- ボタンエリア -->
        <div class="button__container">
            @if($request && $request->request_status_id === 1)
            <button type="submit" class="btn-submit">承認</button>
            @else
            <div class="approve-btn">承認済み</div>
            @endif
        </div>
    @if($request)
    </form>
    @endif
</div>
@endsection