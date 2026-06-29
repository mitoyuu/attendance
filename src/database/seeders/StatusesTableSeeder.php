<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $params = [
            [
                'id' => 1,
                'status' => '勤務外',
            ],
            [
                'id' => 2,
                'status' => '出勤中',
            ],
            [
                'id' => 3,
                'status' => '休憩中',
            ],
            [
                'id' => 4,
                'status' => '退勤済',
            ],
        ];

        foreach ($params as $param) {

            Status::updateOrCreate(
                ['id' => $param['id']],
                $param
            );
        }
    }
}
