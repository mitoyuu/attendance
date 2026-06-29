<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_record_id',
        'requested_clock_in',
        'requested_clock_out',
        'reason',
        'request_status_id',
    ];

    public function attendance()
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function requestStatus()
    {
        return $this->belongsTo(RequestStatus::class);
    }

    public function breakTimeCorrectionRequests()
    {
        return $this->hasMany(BreakTimeCorrectionRequest::class);
    }
}
