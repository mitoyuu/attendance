<?php
// 勤怠表示・申請
namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\BreakTimeCorrectionRequest;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use App\Http\Requests\AttendanceDetailRequest;


class AttendanceController extends Controller
{
    // 勤怠一覧
    public function index(Request $request)
    {
        // 月を取得（なければ今月）
        $month = $request->month ?? Carbon::now()->format('Y-m');
        // 月の開始と終了（この月の範囲を日付オブジェクトに変換して作る）
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        // 今ログインしているユーザーの勤怠だけ取得
        $attendances = AttendanceRecord::where('user_id', Auth::id())
            // 休憩データも一緒に取得（N+1問題対策）
            ->with('breakTimes')
            // この月のデータだけ取得
            ->whereBetween('work_date', [$start, $end])
            // DBからデータを全部取ってくる
            ->get()
            // 日付をキーにした配列に変換
            ->keyBy('work_date');

        $period = CarbonPeriod::create($start, $end);

        return view('attendance.index', compact('attendances', 'period', 'month'));
    }


    // 打刻画面
    public function create()
    {
        $user = Auth::user();

        // 今日の日付
        $today = now()->format('Y-m-d');

        // 今日の勤怠レコードを取得
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        // 今日の勤怠がない かつ statusが退勤済なら勤務外に戻す
        if (!$attendance && $user->status_id == 4) {
            $user->status_id = 1;
            $user->save();
        }

        $status = $user->status_id;

        return view('attendance.create', compact('status'));
    }

    // 出勤処理（ステータス変更 ＋ 打刻記録）
    public function clockIn()
    {
        $user = auth()->user(); // 今ログインしている人を取得

        $today = now()->format('Y-m-d'); // 今日の日付

        // 勤務外じゃないなら処理しない
        if ($user->status_id != 1) {
            return redirect('/attendance');
        }

        // 今日すでに出勤しているかDBで確認
        $existing = AttendanceRecord::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        // もし今日の勤怠がまだ無ければ作る
        if (!$existing) {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'work_date' => $today,
                'clock_in' => Carbon::now(),
            ]);
        }

        // ユーザーの状態を「出勤中」に変更
        $user->status_id = 2;

        // DBに保存
        $user->save();

        return redirect('/attendance');
    }

    // 休憩入
    public function breakStart()
    {
        $user = auth()->user();

        // 出勤中じゃないなら処理しない
        if ($user->status_id != 2) {
            return redirect('/attendance');
        }

        // 今日の「現在進行中の勤務レコード」を取得する
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate(
                'work_date',
            // now()->toDateString()
                today()
        )
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            // ->latest('id')
            ->latest()
            ->firstOrFail();

        if ($attendance) {
            // 休憩テーブルに「休憩開始時刻」を記録（新規作成）
            // ※ attendance_record_id で「どの勤務の休憩か」を紐付ける
            BreakTime::create([
                'attendance_record_id' => $attendance->id,
                'break_start' => Carbon::now(),
            ]);

            // ユーザーのステータスを「休憩中(3)」にする
            $user->status_id = 3;
            $user->save();
        }

        return redirect('/attendance');
    }

    // 休憩戻
    public function breakEnd()
    {
        $user = auth()->user();

        // 休憩中じゃないなら処理しない
        if ($user->status_id != 3) {
            return redirect('/attendance');
        }

        // 未終了休憩を直接取得
        $break = BreakTime::whereHas(
            'attendanceRecord',
            function ($query) use ($user) {

                $query
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->whereDate(
                        'work_date',
                        today()
                    );
            }
        )
            ->whereNull('break_end')
            ->latest()
            ->firstOrFail();

        $break->update([
            'break_end' => now(),
        ]);
        // ステータスを「出勤中(2)」に戻す
            $user->status_id = 2;
            $user->save();

        return redirect('/attendance');
    }

    // 退勤
    public function clockOut()
    {
        $user = auth()->user();

        // 出勤中じゃないなら処理しない
        if ($user->status_id != 2) {
            return redirect('/attendance');
        }

        // 1. 今日の「出勤したけど退勤していない」レコードを1件探す
        $attendance = AttendanceRecord::where('user_id', Auth::id())
            ->whereDate(
                'work_date',
            // now()->toDateString()
            today()

        )
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            // ->latest('id')
            ->latest()
            ->firstOrFail();

        if ($attendance) {
            // 2. もしレコードがあれば、今の時間を「退勤時間」として上書き（Update）
            $attendance->update([
                'clock_out' => Carbon::now(),
            ]);

            // 3. ユーザーのステータスを「退勤済(4)」にする
            $user = auth()->user();
            $user->status_id = 4;
            $user->save();
        }

        return redirect('/attendance');
    }
// 勤怠がない日の詳細へ遷移するためのID作成
    public function prepare($date)
    {
        // 1. ログイン中ユーザーの、その日の勤怠を探す。なければ作る。
        // firstOrCreate(検索条件, [作成時に追加するデータ])
        $attendance = AttendanceRecord::firstOrCreate([
            'user_id' => Auth::id(),
            'work_date' => $date,
        ],
        [
            // 空勤怠と出勤済みを区別する印
            'clock_in' => null,
        ]);

        return redirect("/attendance/detail/{$attendance->id}");
    }

    // 勤怠詳細画面
    public function show($id)
    {
        // 勤怠ID〇〇を探して休憩・修正申請情報もまとめて取得
        $attendance = AttendanceRecord::with(
            'breakTimes',
            'stampCorrectionRequests.breakTimeCorrectionRequests'
        )->findOrFail($id);

        $stampCorrectionRequest =
            $attendance->stampCorrectionRequests
            ->sortByDesc('created_at')
            ->first();

        $breakRequests =
            $stampCorrectionRequest
            ? $stampCorrectionRequest
            ->breakTimeCorrectionRequests
            : collect();

        return view('attendance.detail', compact('attendance', 'stampCorrectionRequest', 'breakRequests'));
    }

    // 勤怠詳細申請
    public function edit(AttendanceDetailRequest $request)
    {
        // 日付を取得（例：2026-03-21）
        $date = Carbon::parse($request->work_date)->format('Y-m-d');

        // 出勤時間
        $clockIn = $request->requested_clock_in
        // あれば（真）
        ? Carbon::parse($date . ' ' . $request->requested_clock_in)
        // なければ（偽）
        : null;

        // 退勤時間
        $clockOut = $request->requested_clock_out
        ? Carbon::parse($date . ' ' . $request->requested_clock_out)
        : null;

        // 修正申請作成（親レコードを作る）
        $stampCorrection = StampCorrectionRequest::create([
            'attendance_record_id' => $request->attendance_id,
            'requested_clock_in' => $clockIn,
            'requested_clock_out' => $clockOut,
            'reason' => $request->reason,
            'request_status_id' => 1,
        ]);

        // 複数の休憩（休憩をループで作る）
        foreach ($request->breaks as $break) {
            // 休憩開始・終了がどちらも空（未入力）の場合、保存をスキップ
            if (empty($break['start']) && empty($break['end'])) {
                continue;
            }
            // 休憩開始
            $breakStart = $break['start']
                ? Carbon::parse($date . ' ' . $break['start'])
                : null;

            // 休憩終了
            $breakEnd = $break['end']
                ? Carbon::parse($date . ' ' . $break['end'])
                : null;

            // 休憩修正を保存（子レコード）
            BreakTimeCorrectionRequest::create([
                'stamp_correction_request_id' => $stampCorrection->id,
                'requested_break_start' => $breakStart,
                'requested_break_end' => $breakEnd,
            ]);
        }
        // 詳細画面へ戻る
        return redirect('/attendance/detail/' . $request->attendance_id);
    // 休憩は複数登録できるため、フロントでは breaks[index][start/end] の配列形式で送信。
    // サーバー側では、まず勤怠修正申請（親レコード）を1件作成し、
    // その後、休憩配列をループ処理して子レコードとして保存。
    // 開始・終了が未入力の休憩は continue により保存をスキップ
    }
}
