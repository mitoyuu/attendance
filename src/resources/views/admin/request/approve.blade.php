@extends('layouts.default')

@section('css')
<!-- 管理者 申請承認 -->
<!-- 勤怠詳細からコピペ修正未 -->
<link rel="stylesheet" href="{{ asset('/css/admin/request/approve.css')  }}">
@endsection

@section('content')
<!-- 本体 -->

@include('components.admin_header')
@if($request)
<form action="/admin/stamp_correction_request/approve/{{$request->id}}" method="post" class="attendance__detail">
@endif
    @csrf
    <h1 class="page__title">勤怠詳細</h1>
    <div class="form__group">
        <label for="name" class="entry__name">名前</label>
        <span>{{ $attendance->user->name }}</span>
    </div>
    <div class="form__group">
        <label for="work_date" class="entry__name">日付</label>
        {{-- 表示用 --}}
        <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年n月j日') }}</span>
        {{-- 送信用（隠す） --}}
        <input type="hidden" name="work_date" value="{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d') }}">
    </div>
    <div class="form__group">
        <label for="requested_clock" class="entry__name">出勤・退勤</label>
        @if($request)
        <span> {{ $request->requested_clock_in ? \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i') : '' }}
        </span>
        〜
        <span> {{ $request->requested_clock_out ? \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i') : '' }}
        </span>
        @else
        <input name="requested_clock_in" id="requested_clock" type="text" class="input" value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
        〜
        <input name="requested_clock_out" id="requested_clock" type="text" class="input" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
        @endif
    </div>
    <div class="form__group">
        <label class="entry__name">休憩</label>
        @if($request)

        @foreach($breakRequests as $break)
        <span> {{ $break->requested_break_start ? \Carbon\Carbon::parse($break->requested_break_start)->format('H:i') : '' }}
        </span>
        〜
        <span> {{ $break->requested_break_end ? \Carbon\Carbon::parse($break->requested_break_end)->format('H:i') : '' }}
        </span>
        @endforeach

        @else
        @foreach($attendance->breakTimes as $index => $break)
        <div class="break-row">
            <input name="breaks[{{ $index }}][start]" type="text" class="input"
                value="{{ $break->break_start
                ? \Carbon\Carbon::parse($break->break_start)->format('H:i')
                : '' }}">
            〜
            <input name="breaks[{{ $index }}][end]" type="text" class="input"
                value="{{ $break->break_end
                ? \Carbon\Carbon::parse($break->break_end)->format('H:i')
                : '' }}">
        </div>
        @endforeach

        <!-- {{-- 追加の1行 --}} -->
        <div class="break-row">
            <input name="breaks[{{ $attendance->breakTimes->count() }}][start]" type="text" class="input" value="">
            〜
            <input name="breaks[{{ $attendance->breakTimes->count() }}][end]" type="text" class="input" value="">
        </div>
        @endif
    </div>
    <div class="form__group">
        <label for="reason" class="entry__name">備考</label>
        @if($request)
        <span> {{ $request->reason }}
        </span>
        @else
        <input name="reason" id="reason" type="text" class="input" value="">
        @endif
    </div>

    <!-- どの勤怠の修正なのか分かるようにattendance_idを取得するための記述 -->
    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

    <!-- {{-- 1. 申請中（ステータスIDが1）のデータが存在するかチェック --}} -->
    @if($request && $request->request_status_id === 1)
    <!-- {{-- 存在する場合：承認ボタンを表示 --}} -->
    <button type="submit" class="btn-submit">承認</button>
    @else
    <!-- {{-- 1(承認待ち)ではない場合：承認済みを表示 --}} -->
    <div class="approve-btn">承認済み</div>
    @endif

</form>
@endsection
