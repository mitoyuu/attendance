<?php
// 管理者

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use App\Http\Requests\AttendanceDetailRequest;

class AdminAttendanceController extends Controller
{
    // 勤怠一覧
    public function index(Request $request)
    {
        // 日付を取得（なければ今日）
        $date = $request->date ?? Carbon::now()->format('Y-m-d');

        // その日の全ユーザーの勤怠一覧取得
        $attendances = AttendanceRecord::with('user', 'breakTimes')
            ->whereDate('work_date', $date)
            ->get();

        return view('admin.attendance.index', compact('attendances', 'date'));
    }

    // 勤怠詳細画面
    public function show($id)
    {
        $attendance = AttendanceRecord::with('user', 'breakTimes', 'stampCorrectionRequests')->find($id);

        $request = $attendance->stampCorrectionRequests
            ->where('request_status_id', 1)
            ->first();

        // $request があるなら、その申請に紐づく休憩データ全部取得
        $breakRequests = $request
            ? $request->breakTimeCorrectionRequests
            // もし$requestが無かったら、空っぽの箱を用意
            : collect();

        return view('admin.attendance.detail', compact('attendance', 'request', 'breakRequests'));
    }

    public function edit(AttendanceDetailRequest $request,$id)
    {
        // 更新対象取得
        $attendance =
            AttendanceRecord::with(
                'breakTimes'
            )->findOrFail($id);

        $date = Carbon::parse(
            $request->work_date
        )->format('Y-m-d');

        // 出退勤更新
        $attendance->update([

            'clock_in' =>
            $request->requested_clock_in
                ? Carbon::parse(
                    $date . ' ' .
                        $request->requested_clock_in
                )
                : null,

            'clock_out' =>
            $request->requested_clock_out
                ? Carbon::parse(
                    $date . ' ' .
                        $request->requested_clock_out
                )
                : null,
        ]);

        // 既存休憩削除
        $attendance
            ->breakTimes()
            ->delete();

        // 再登録
        foreach (
            $request->breaks
            as $break
        ) {

            if (
                empty($break['start'])
                &&
                empty($break['end'])
            ) {
                continue;
            }

            $attendance
                ->breakTimes()
                ->create([

                    'break_start' =>
                    $break['start']
                        ? Carbon::parse(
                            $date .
                                ' ' .
                                $break['start']
                        )
                        : null,

                    'break_end' =>
                    $break['end']
                        ? Carbon::parse(
                            $date .
                                ' ' .
                                $break['end']
                        )
                        : null,
                ]);
        }

        return redirect(
            '/admin/attendance/' . $id
        );
    }
}
