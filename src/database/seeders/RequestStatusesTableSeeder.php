<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequestStatus;

class RequestStatusesTableSeeder extends Seeder
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
                'request_status' => '承認待ち',
            ],
            [
                'id' => 2,
                'request_status' => '承認済み',
            ],
        ];

        foreach ($params as $param) {
            RequestStatus::updateOrCreate(
                ['id' => $param['id']],
                $param
            );
        }
    }
}
