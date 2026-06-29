<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;


class AdminStaffController extends Controller
{
    // スタッフ一覧
    public function index()
    {
        $users = User::all();

        return view('admin.staff.index',compact('users'));
    }

    // 勤怠がない日の詳細へ遷移するためのID作成
    public function prepare($userId, $date)
    {
        $attendance = AttendanceRecord::firstOrCreate([
            'user_id' => $userId,
            'work_date' => $date,
        ]);

        return redirect("/admin/attendance/{$attendance->id}");
    }

    // スタッフ別勤怠一覧
    public function attendance($id, Request $request)
    {
        $user = User::findOrFail($id);

        $month = $request->month
            ?? Carbon::now()->format('Y-m');

        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $attendances = AttendanceRecord::where('user_id', $id)
            ->with('breakTimes')
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy('work_date');

        $period = CarbonPeriod::create($start, $end);

        return view('admin.staff.attendance',
            compact('user','attendances','period','month'));
    }

    // CSV出力
    public function downloadCsv($id, Request $request)
    {
        // ユーザー情報の取得
        $user = User::findOrFail($id);

        // 選択された月の取得（なければ当月）
        $month = $request->month ?? Carbon::now()->format('Y-m');

        // 月の初日と末日の計算
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        // 該当月の勤怠データを取得（※keyByや休憩のEagerロードはCSV用には不要なので外して日付順にします）
        $attendances = AttendanceRecord::where('user_id', $id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function($attendance)
            {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });


        // ダウンロードされるファイルのファイル名を指定
        // ファイル名（例: 山田太郎_2026-06_勤怠一覧.csv）
        $fileName = "{$user->name}_{$month}_勤怠一覧.csv";

        // ストリームレスポンスでCSVを生成
        $response = new StreamedResponse(function () 
        use ($attendances,    $start,$end) {
            $stream = fopen('php://output', 'w');

            // Excelの日本語文字化け対策（BOMを追加）
            fwrite($stream, pack('C*', 0xEF, 0xBB, 0xBF));

            // CSVのヘッダー（見出し行）
            fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

            $period = CarbonPeriod::create($start, $end);

            // 画面と同じように1ヶ月分の日付（$period）でループを作ると、勤務がない日も空欄で綺麗に出力
            foreach ($period as $date) {
                $attendance = $attendances->get($date->format('Y-m-d'));

                fputcsv($stream, [
                    $date->isoFormat('MM/DD(ddd)'), // 日付
                    $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '', // 出勤
                    $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '', // 退勤
                    $attendance ? $attendance->break_total_format : '', // 休憩
                    $attendance ? $attendance->work_total_format : '',  // 合計
                ]);
            }

            // ストリームを閉じる
            fclose($stream);
        });

        // レスポンスヘッダー（ファイルダウンロード用の設定）を追加
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
