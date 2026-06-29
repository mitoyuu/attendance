    <header class="header">
        <div class="header__logo">
            <a href="/attendance"><img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="ロゴ"></a>
        </div>
        <nav class="header__nav">
            <ul>
                @if(Auth::check() && Auth::user()->hasVerifiedEmail())
                <!-- 今いる画面が勤怠登録画面かつ、ユーザーが退勤済なら
                表示 -->
                @if(request()->is('attendance')
                &&
                Auth::user()->status_id == 4)
                <li><a href="/attendance/list">今月の出勤一覧</a></li>
                <li><a href="/stamp_correction_request/list">申請一覧</a></li>
                @else
                <li><a href="/attendance">勤怠</a></li>
                <li><a href="/attendance/list">勤怠一覧</a></li>
                <li><a href="/stamp_correction_request/list">申請</a></li>
                @endif
                <li>
                    <form action="/logout" method="post">
                        @csrf
                        <button class="header__logout" type="submit">ログアウト</button>
                    </form>
                </li>
                @endif
            </ul>
        </nav>
    </header>