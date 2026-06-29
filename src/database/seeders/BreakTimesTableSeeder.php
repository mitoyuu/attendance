<?php

namespace Database\Seeders;

use App\Models\BreakTime;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class BreakTimesTableSeeder extends Seeder
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
            'break_start' => Carbon::parse('12:00'),
            'break_end' => Carbon::parse('13:00'),
        ];
        BreakTime::create($param);

        $param = [
            'attendance_record_id' => 2,
            'break_start' => Carbon::parse('12:00'),
            'break_end' => Carbon::parse('13:00'),
        ];
        BreakTime::create($param);
    }
}
