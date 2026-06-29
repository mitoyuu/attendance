<header class="header">
    <div class="header__logo">
        <a href="/admin/attendance/list">
            <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="ロゴ">
        </a>
    </div>

    <nav class="header__nav">
        <ul>
            @if(Auth::check())
            <li><a href="/admin/attendance/list">勤怠一覧</a>
            </li>
            <li><a href="/admin/staff/list">スタッフ一覧</a>
            </li>
            <li><a href="/admin/stamp_correction_request/list">申請一覧</a>
            </li>

            <li>
                <form action="/logout" method="post">
                    @csrf
                    <button class="header__logout" type="submit">
                        ログアウト
                    </button>
                </form>
            </li>
            @endif
        </ul>
    </nav>
</header>