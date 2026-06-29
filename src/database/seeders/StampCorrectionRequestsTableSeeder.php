<?php

namespace Database\Seeders;

use App\Models\StampCorrectionRequest;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class StampCorrectionRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'attendance_record_id' => 1,
            'requested_clock_in' => Carbon::parse('10:00'),
            'requested_clock_out' => Carbon::parse('19:00'),
            'reason' => '遅延のため',
            'request_status_id' => 1,
        ];
        StampCorrectionRequest::create($param);
    }
}
