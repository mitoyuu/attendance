<?php
// <!-- 申請一覧 -->

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\BreakTimeCorrectionRequest;
use App\Models\StampCorrectionRequest;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        // URLのクエリパラメータを取得
        $tab = $request->query('tab', 'pending');

        // リレーション（勤怠・ユーザー・申請ステータス）を事前にまとめて取得（N+1対策）
        $stampCorrections = StampCorrectionRequest::with(['attendance.user', 'requestStatus'])
        // ログインユーザーの申請だけ取得
            ->whereHas('attendance', function ($query) {
            $query->where('user_id', Auth::id());
        })
        // タブによる条件分岐
        // approvedなら「2:承認済み」、それ以外は「1:承認待ち」
            ->when($tab === 'approved', function ($query) {
            $query->where('request_status_id', 2);
        }, function ($query) {
            $query->where('request_status_id', 1);
        })
            ->get();

        return view('request.index', compact('stampCorrections', 'tab'));
    }
}
