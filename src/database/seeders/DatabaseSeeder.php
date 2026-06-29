<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(StatusesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(AttendanceRecordsTableSeeder::class);
        $this->call(BreakTimesTableSeeder::class);
        $this->call(RequestStatusesTableSeeder::class);
        $this->call(StampCorrectionRequestsTableSeeder::class);
    }
}
