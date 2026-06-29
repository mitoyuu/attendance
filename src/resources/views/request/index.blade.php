@extends('layouts.default')

@section('css')
{{-- 申請一覧(一般・管理者共通) --}}
<link rel="stylesheet" href="{{ asset('/css/request/index.css')  }}">
@endsection

@section('content')
{{-- 本体 --}}

@if(Auth::user()->role === 1)
<!-- 管理者のヘッダー -->
@include('components.admin_header')
@else
<!-- 一般ユーザーのヘッダー -->
@include('components.header')
@endif

<h1 class="page__title">申請一覧</h1>
<!-- タブ -->
<a href="?tab=pending">承認待ち</a>
<a href="?tab=approved">承認済み</a>
<table>
    <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($stampCorrections as $stampCorrection)
        <tr>
            <!-- 状態 -->
            <td> {{ $stampCorrection->requestStatus->request_status }}

            </td>
            <!-- 名前 -->
            <td>{{ $stampCorrection->attendance->user->name }}</td>
            <!-- 対象日時 -->
            <td>{{ \Carbon\Carbon::parse($stampCorrection->attendance->work_date)->format('Y/m/d') }}
            </td>
            <!-- 申請理由 -->
            <td>{{ $stampCorrection->reason }}
            </td>
            <!-- 申請日時 -->
            <td>{{ $stampCorrection->created_at->format('Y/m/d') }}
            </td>
            <!-- 詳細 -->
            <td>

                @if($stampCorrection->attendance)
                <!-- 管理者 -->
                @if(Auth::user()->role === 1)
                <a href="/admin/stamp_correction_request/approve/{{ $stampCorrection->attendance->id }}">
                    詳細
                </a>
                @else
                <!-- 管理者でない（＝一般ユーザー） -->
                <a href="/attendance/detail/{{ $stampCorrection->attendance->id }}">
                    詳細
                </a>

                @endif

                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection