<?php
// <!-- 申請承認・却下 -->

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\BreakTimeCorrectionRequest;
use App\Models\StampCorrectionRequest;
use App\Models\BreakTime;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AdminStampCorrectionRequestController extends Controller
{
    //     承認処理の例
    // $request->request_status_id = RequestStatus::APPROVED;
    // $request->save();

    // モデルで下記を定義済み（2026/04/03）
    // const PENDING = 1;
    // const APPROVED = 2;
    // const REJECTED = 3;

    // 申請一覧
    public function index(Request $request)
    {
    // URLのクエリパラメータを取得
        $tab = $request->query('tab', 'pending');

        // リレーション（勤怠・ユーザー・申請ステータス）を事前にまとめて取得（N+1対策）
        $stampCorrections = StampCorrectionRequest::with(['attendance.user', 'requestStatus'])

            // タブによる条件分岐
            // approvedなら「2:承認済み」、それ以外は「1:承認待ち」
            ->when($tab === 'approved', function ($query) {
                $query->where('request_status_id', 2);
            }, function ($query) {
                $query->where('request_status_id', 1);
            })
            ->get();
        // dd($stampCorrections);

        return view('request.index', compact('stampCorrections', 'tab'));
    }

    // 修正申請承認画面
    public function show($id)
    {
        $attendance = AttendanceRecord::with('breakTimes', 'stampCorrectionRequests')->find($id);

        // この勤怠の申請を
        $request = $attendance->stampCorrectionRequests()
            // 新しい順（最新）に並べて
            ->latest()
            // 先頭の1件を取る
            ->first();

        // $request があるなら、その申請に紐づく休憩データ全部取得
        $breakRequests = $request
            ? $request->breakTimeCorrectionRequests
            // もし$requestが無かったら、空っぽの箱を用意
            : collect();

        return view('admin.request.approve', compact('attendance', 'request', 'breakRequests'));
    }

    // 修正申請の承認
    //     public function approve($id)
    //     {
    //         // 承認する申請を取得
    //         $stampCorrection = StampCorrectionRequest::findOrFail($id);

    //         // 対象勤怠取得
    //         $attendance = AttendanceRecord::findOrFail(
    //             $stampCorrection->attendance_record_id
    //         );

    //         // 勤怠更新
    //         $attendance->clock_in =
    //             $stampCorrection->requested_clock_in;

    //         $attendance->clock_out =
    //             $stampCorrection->requested_clock_out;

    //         // DBに保存
    //         // $attendance->save();

    //         // 休憩更新
    //         foreach (
    //             $stampCorrection->breakTimeCorrectionRequests
    //             as $index => $breakRequest
    //         ) {

    //             $break =
    //                 $attendance->breakTimes[$index]
    //                 ?? null;

    //             if (!$break) {

    //                 $break = new BreakTime();

    //                 $break->attendance_record_id =
    //                     $attendance->id;
    //             }

    //                 $break->break_start =
    //                     $breakRequest->requested_break_start;

    //                 $break->break_end =
    //                     $breakRequest->requested_break_end;

    //                 $break->save();
    //             }

    //         // // リレーション再取得
    //         // $attendance->load('breakTimes');

    //         // // break_total を再計算
    //         // $total = 0;

    //         // foreach ($attendance->breakTimes as $break) {

    //         //     if (
    //         //         $break->break_start
    //         //         &&
    //         //         $break->break_end
    //         //     ) {

    //         //         $total +=
    //         //             strtotime($break->break_end)
    //         //             -
    //         //             strtotime($break->break_start);
    //         //     }
    //         // }

    //         // $attendance->break_total =
    //         //     $total;

    //         // // work_total計算
    //         // $workTotal =
    //         //     \Carbon\Carbon::parse(
    //         //         $attendance->clock_out
    //         //     )->diffInSeconds(
    //         //         \Carbon\Carbon::parse(
    //         //             $attendance->clock_in
    //         //         )
    //         //     );

    //         // $attendance->work_total =
    //         //     $workTotal
    //         //     // ($workTotal * 60)
    //         //     -
    //         //     $attendance->break_total;

    //         // DBに保存
    //         $attendance->save();
    //         // 既存休憩削除
    //         $attendance
    //             ->breakTimes()
    //             ->delete();

    //         foreach (
    //             $stampCorrection
    //                 ->breakTimeCorrectionRequests
    //             as $breakRequest
    //         ) {

    //             BreakTime::create([
    //                 'attendance_record_id'
    //                 => $attendance->id,

    //                 'break_start'
    //                 =>
    //                 $breakRequest
    //                     ->requested_break_start,

    //                 'break_end'
    //                 =>
    //                 $breakRequest
    //                     ->requested_break_end,
    //             ]);
    //         }

    //         // ステータスを承認待ち（1）から承認（2）に変更
    //         $stampCorrection->request_status_id = 2;

    //         // DBに保存
    //         $stampCorrection->save();


    //         // 承認後に勤怠詳細へ戻る
    //         return redirect('/admin/stamp_correction_request/approve/'
    //         . $attendance->id);
    //     }
    // }
    public function approve($id)
    {
        // 承認する申請取得
        $stampCorrection =
            StampCorrectionRequest::findOrFail($id);

        // 対象勤怠取得
        $attendance =
            AttendanceRecord::findOrFail(
                $stampCorrection
                    ->attendance_record_id
            );

        // 勤怠更新
        $attendance->clock_in =
            $stampCorrection
            ->requested_clock_in;

        $attendance->clock_out =
            $stampCorrection
            ->requested_clock_out;

        // 勤怠保存
        $attendance->save();

        // 既存休憩削除
        $attendance
            ->breakTimes()
            ->delete();

        // 修正申請休憩を再登録
        foreach (
            $stampCorrection
                ->breakTimeCorrectionRequests
            as $breakRequest
        ) {

            BreakTime::create([
                'attendance_record_id'
                => $attendance->id,

                'break_start'
                =>
                $breakRequest
                    ->requested_break_start,

                'break_end'
                =>
                $breakRequest
                    ->requested_break_end,
            ]);
        }

        // 承認済みに変更
        $stampCorrection
            ->request_status_id = 2;

        $stampCorrection
            ->save();

        return redirect(
            '/admin/stamp_correction_request/approve/'
                . $attendance->id
        );
    }
}