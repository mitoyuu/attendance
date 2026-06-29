<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '一般ユーザ1',
            'email' => 'general1@gmail.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'role' => 0,
            'status_id' => 1,
        ];
        User::create($param);

        $param = [
            'name' => '一般ユーザ2',
            'email' => 'general2@gmail.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'role' => 0,
            'status_id' => 1,
        ];
        User::create($param);

        $param = [
            'name' => '管理者',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'status_id' => 1,
        ];
        User::create($param);

        User::factory()->count(10)->create();
    }
}
