<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Database\Seeders\StatusesTableSeeder;
use Database\Seeders\RequestStatusesTableSeeder;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            StatusesTableSeeder::class,
            RequestStatusesTableSeeder::class,
        ]);
    }

    // その日になされた全ユーザーの勤怠情報を管理者が正確に確認できるか
    public function test_all_users_attendance_are_displayed_correctly()
    {
        Carbon::setTestNow('2026-06-26');

        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // 一般ユーザー
        $user1 = User::factory()->create([
            'name' => '一般ユーザーA',
        ]);

        $user2 = User::factory()->create([
            'name' => '一般ユーザーB',
        ]);

        // 勤怠作成
        AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'work_date' => now()->format('Y-m-d'),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'work_date' => now()->format('Y-m-d'),
        ]);

        // 一覧取得
        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list');

        // 確認
        $response
            ->assertStatus(200)
            ->assertSee('一般ユーザーA')
            ->assertSee('一般ユーザーB');

        Carbon::setTestNow();
    }

    // 画面に今日の日付が表示されているか
    public function test_current_date_is_displayed_when_admin_opens_attendance_list()
    {
        Carbon::setTestNow('2026-06-26');

        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list');

        $response
            ->assertStatus(200)
            ->assertSee('2026年6月26日の勤怠');

        Carbon::setTestNow();
    }

    // 前日表示
    public function test_previous_day_button_shows_previous_day_attendance()
    {
        Carbon::setTestNow('2026-06-26');

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // 前日データ
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => $yesterday,
        ]);

        // 前日ボタン押下（クエリ想定）
        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $yesterday);

        $response
            ->assertStatus(200)
            ->assertSee(
                Carbon::parse($yesterday)->format('Y年n月j日')
            )
            ->assertSee('テストユーザー');

        Carbon::setTestNow();
    }

    // 翌日表示
    public function test_next_day_button_shows_next_day_attendance()
    {
        Carbon::setTestNow('2026-06-26');

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $tomorrow = Carbon::now()->addDay()->format('Y-m-d');

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => $tomorrow,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $tomorrow);

        $response
            ->assertStatus(200)
            ->assertSee(
                Carbon::parse($tomorrow)->format('Y年n月j日')
            )
            ->assertSee('テストユーザー');

        Carbon::setTestNow();
    }
}
