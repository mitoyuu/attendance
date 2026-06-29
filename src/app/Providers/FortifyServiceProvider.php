<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\LoginResponse; // 追加
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // メール認証
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // 会員登録
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
        return view('auth.register');
        });

        // ログイン画面
        Fortify::loginView(function () {
            if (request()->is('admin/login')) {
                return view('admin.login'); // 管理者
            }
        return view('auth.login');
        });

        //デフォルトのログイン機能にあるフォームリクエストを自作のものに代替するため、サービスコンテナにバインド
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

        // ログイン後の遷移（管理者）
        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                if (auth()->user()->role === 1) {
                    return redirect('/admin/attendance/list');
                }
                return redirect('/attendance');
            }
        });
        // ログイン後の遷移（一般ユーザー）
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect('/login');
            }
        });

        // 制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
