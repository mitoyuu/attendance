<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        // 'break_total',
        // 'work_total',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    // 休憩時間の合計秒数を計算する機械
    // break_total という仮想カラムを作る（ ＝ $attendance->break_totalが使える）
    public function getBreakTotalAttribute()
    {
        // 休憩合計を入れる箱
        $total = 0;

        // この勤務にある全部の休憩
        foreach ($this->breakTimes as $break) {
            // 休憩終了があるか？（休憩開始だけ押して終了押してない可能性あるから必要）
            if ($break->break_end) {

                $start = Carbon::parse($break->break_start);
                $end = Carbon::parse($break->break_end);
                // 2つの時間の差（秒）
                $total += $end->diffInSeconds($start);
            }
        }
        // 休憩合計を返す
        return $total;
    }

    // 勤務時間の合計秒数を計算
    // work_total という仮想カラム
    public function getWorkTotalAttribute()
    {
        if (!$this->clock_out) {
            return 0;
        }

        $start = Carbon::parse($this->clock_in);
        $end = Carbon::parse($this->clock_out);
        // 勤務時間（秒） = 2つの時間の差（秒）
        // (勤務秒数 = 終了 - 開始)
        $workSeconds = $end->diffInSeconds($start);

        return $workSeconds - $this->break_total;
    }

    // 秒数を渡したら「時間:分(1:05)」形式に変換する共通メソッド
    private function formatSeconds($seconds)
    {
        return intdiv($seconds, 3600) . ':' .
            str_pad(intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT);
    }

    // 休憩合計時間を表示用に変換
    public function getBreakTotalFormatAttribute()
    {
        // 出勤していない勤怠は空文字を返す
        if (!$this->clock_out) {
            return '';
        }

        return $this->formatSeconds($this->break_total);
    }

    // 勤務合計時間を表示用に変換
    public function getWorkTotalFormatAttribute()
    {
        if (!$this->clock_out) {
            return '';
        }

        return $this->formatSeconds($this->work_total);
    }
}
