@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/verify.css')  }}">
@endsection

@section('content')
@include('components.header')
<div class="mail_notice--div">
    <!-- <div class="mail_notice--header">
        <p class="notice_header--p">メール認証はお済みですか？</p>
    </div> -->

    <div class="mail_notice--content">

        @if (session('status') == 'verification-link-sent')
        <p class="notice_resend--p">
            新規認証メールを再送信しました！
        </p>
        @endif
        <p class="alert_resend--p">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        <a
            href="https://mailtrap.io/sandboxes"
            target="_blank"
            class="mail_verify--button">認証はこちらから</button>
        </a>
        <form class="mail_resend--form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="mail_resend--link">認証メールを再送する
            </button>
        </form>
    </div>
    <!-- @if (session('resent'))
        <p class="notice_resend--p" role="alert">
            新規認証メールを再送信しました！
        </p>
        @endif -->
</div>
@endsection