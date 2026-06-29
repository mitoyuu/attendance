<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AdminStampCorrectionRequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ログアウト時、ルートパスにアクセスしたらログイン画面へリダイレクトさせる
Route::get('/', function () {
    return redirect('/login');
});

// 一般ユーザー
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠打刻画面
    Route::get('/attendance', [AttendanceController::class, 'create']);

    // 勤怠打刻
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    // 休憩入
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart']);
    // 休憩戻
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd']);
    // 退勤
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'index']);

    // 勤怠がない日の詳細へ遷移するためのID作成
    Route::get('/attendance/prepare/{date}'
    , [AttendanceController::class, 'prepare']);

    // 勤怠詳細
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show']);
    // 勤怠詳細申請
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'edit']);

    // 修正申請一覧
    Route::get('/stamp_correction_request/list',[StampCorrectionRequestController::class, 'index']);
});

// 管理者用ログイン
Route::get('/admin/login', function () {
    return view('admin.login');
});
// 管理者
Route::prefix('admin')->middleware(['auth', 'admin', 'verified'])->group(function () {
    // 勤怠一覧
    Route::get('/attendance/list',[AdminAttendanceController::class, 'index']);
    // 勤怠詳細
    Route::get('/attendance/{id}',[AdminAttendanceController::class, 'show']);
    // 勤怠詳細（直接更新）
    Route::post('/attendance/{id}',
    [AdminAttendanceController::class, 'edit']);
    // スタッフ一覧
    Route::get('/staff/list',[AdminStaffController::class, 'index']);

    // 勤怠がない日の詳細へ遷移するためのID作成
    Route::get('/attendance/prepare/{userId}/{date}',
    [AdminStaffController::class, 'prepare']);

    // スタッフ別勤怠一覧
    Route::get('/attendance/staff/{id}',[AdminStaffController::class, 'attendance']);
    Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'downloadCsv'])->name('attendance.csv');

    // 申請一覧
    Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index']);
    // 申請詳細
    Route::get('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'show']);
    // 承認
    Route::post('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'approve']);
    });

