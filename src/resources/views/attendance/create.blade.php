@extends('layouts.default')
<!-- 出勤登録 -->

<!-- タイトル -->
@section('title','勤怠打刻')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/create.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

@include('components.header')
<div class="attendance">

    <p class="status">
        @if($status == 1)
        勤務外
        @elseif($status == 2)
        出勤中
        @elseif($status == 3)
        休憩中
        @elseif($status == 4)
        退勤済
        @endif
    </p>

    <h2> {{ now()->isoFormat('YYYY年M月D日(ddd)') }}
    </h2>

    <h1>{{ now()->format('H:i') }}</h1>

    <div class="buttons">

        @if($status == 1)
        <form method="POST" action="/attendance/clock-in">
            @csrf
            <button>出勤</button>
        </form>

        @elseif($status == 2)
        <form method="POST" action="/attendance/clock-out">
            @csrf
            <button>退勤</button>
        </form>

        <form method="POST" action="/attendance/break-start">
            @csrf
            <button>休憩入</button>
        </form>

        @elseif($status == 3)
        <form method="POST" action="/attendance/break-end">
            @csrf
            <button>休憩戻</button>
        </form>

        @elseif($status == 4)

        <p>お疲れ様でした。</p>

        @endif

    </div>
</div>
@endsection