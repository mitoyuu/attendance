@extends('layouts.default')

@section('css')
{{-- 勤怠詳細のCSS --}}
    <link rel="stylesheet" href="{{ asset('/css/attendance/detail.css') }}">
@endsection

@section('content')
{{-- 本体 --}}
    @include('components.header')
    <form action="/attendance/detail/{{ $attendance->id }}" method="post" class="attendance__detail">
        @csrf
        <h1 class="page__title">勤怠詳細</h1>
        <div class="form__group">
            <label for="name" class="entry__name">名前</label>
            <span>{{ Auth::user()->name }}</span>
        </div>
        <div class="form__group">
            <label for="work_date" class="entry__name">日付</label>
            {{-- 表示用 --}}
            <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年n月j日') }}</span>
            {{-- 送信用（隠す） --}}
            <input type="hidden" name="work_date"
                value="{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d') }}">
        </div>
        <div class="form__group">
            <label for="requested_clock" class="entry__name">出勤・退勤</label>
            @if ($stampCorrectionRequest)
                <span>
                    {{ $stampCorrectionRequest->requested_clock_in ? \Carbon\Carbon::parse($stampCorrectionRequest->requested_clock_in)->format('H:i') : '' }}
                </span>
                〜
                <span>
                    {{ $stampCorrectionRequest->requested_clock_out ? \Carbon\Carbon::parse($stampCorrectionRequest->requested_clock_out)->format('H:i') : '' }}
                </span>
            @else
                <input name="requested_clock_in" id="requested_clock" type="text" class="input"
                    value="{{ old('requested_clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}">
                〜
                <input name="requested_clock_out" id="requested_clock" type="text" class="input"
                    value="{{ old('requested_clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                <div class="form__error">
                    @error('requested_clock_out')
                        {{ $message }}
                    @enderror
                </div>
            @endif
        </div>
        {{-- 休憩 --}}
        <div class="form__group-break-container">
            @if ($stampCorrectionRequest)
                {{-- 承認済み・申請済み表示専用 --}}
                @foreach ($breakRequests as $index => $break)
                    <div class="form__group">
                        <label class="entry__name">
                            {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                        </label>
                        <span>
                            {{ $break->requested_break_start ? \Carbon\Carbon::parse($break->requested_break_start)->format('H:i') : '' }}
                        </span>
                        〜
                        <span>
                            {{ $break->requested_break_end ? \Carbon\Carbon::parse($break->requested_break_end)->format('H:i') : '' }}
                        </span>
                    </div>
                @endforeach
            @else
                {{-- 編集フォーム側 --}}
                @php
                    $breakCount = $attendance->breakTimes->count();
                    $displayCount = max(2, $breakCount + 1);
                @endphp

                @for ($i = 0; $i < $displayCount; $i++)
                    <div class="form__group">
                        <label class="entry__name">
                            {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                        </label>
                        <div class="break-row">
                            <input name="breaks[{{ $i }}][start]" type="text" class="input"
                                value="{{ old(
                                    'breaks.' . $i . '.start',
                                    isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_start
                                        ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_start)->format('H:i')
                                        : '',
                                ) }}">
                            〜
                            <input name="breaks[{{ $i }}][end]" type="text" class="input"
                                value="{{ old(
                                    'breaks.' . $i . '.end',
                                    isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_end
                                        ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_end)->format('H:i')
                                        : '',
                                ) }}">
                            <div class="form__error">
                                @error('breaks.' . $i . '.end')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>
                    </div>
                @endfor
            @endif
        </div>

        <div class="form__group">
            <label for="reason" class="entry__name">備考</label>
            @if ($stampCorrectionRequest)
                <span> {{ $stampCorrectionRequest->reason }}
                </span>
            @else
                <input name="reason" id="reason" type="text" class="input" value="{{ old('reason') }}">
            @endif
            <div class="form__error">
                @error('reason')
                    <p>{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- どの勤怠の修正なのか分かるようにattendance_idを取得するための記述 --}}
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        {{-- 承認待ち --}}
        @if ($stampCorrectionRequest && $stampCorrectionRequest->request_status_id === 1)
            <p class="error-message">
                *承認待ちのため修正はできません。
            </p>
            {{-- 承認済み --}}
        @elseif($stampCorrectionRequest && $stampCorrectionRequest->request_status_id === 2)
            <p class="message">
                *承認済みのため修正できません。
            </p>
        @else
            {{-- 未申請 --}}
            <button type="submit" class="btn-submit">
                修正
            </button>
        @endif
    </form>
@endsection