<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeCorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'stamp_correction_request_id',
        'requested_break_start',
        'requested_break_end',
    ];

    public function stampCorrectionRequests()
    {
        return $this->belongsTo(StampCorrectionRequest::class);
    }
}
