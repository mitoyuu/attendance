<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class AttendanceRecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. ユーザー1と2の「今日」のデータを手動で作成
        AttendanceRecord::create([
            'user_id' => 1,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::parse('09:00'),
            'clock_out' => Carbon::parse('18:00'),
            'break_total' => $breakTotal,
            'work_total' => $workTotal,
        ]);

        AttendanceRecord::create([
            'user_id' => 2,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::parse('09:00'),
            'clock_out' => Carbon::parse('18:00'),
        ]);

        // 2. 全ユーザーを取得
        $users = User::all();

        // 3. 各ユーザーに対して、昨日以前のデータを5日分作成
        foreach ($users as $user) {
            AttendanceRecord::factory()
                ->count(5)
                ->sequence(fn($sequence) => [
                    // sequence->indexは0から始まるので、
                    // subDays(1), subDays(2)...となり、今日(today)とは被らない
                    'work_date' => Carbon::today()->subDays($sequence->index + 1),
                ])
                ->create([
                    'user_id' => $user->id,
                ]);
        }
    }
}