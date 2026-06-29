<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceCreateTest extends TestCase
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

        $this->seed(
            \Database\Seeders\StatusesTableSeeder::class
        );
    }

    // 現在の日時情報がUIと同じ形式で出力される
    public function test_current_datetime_is_displayed()
    {
        // 現在日時を固定
        Carbon::setTestNow(
            Carbon::parse('2026-06-23 09:30:00')
        );
        // ユーザー作成
        $user = User::factory()->create();

        // ログイン状態作成
        $response = $this
            ->actingAs($user)
            ->get('/attendance');

        // Bladeと同じ形式で期待値作成
        $response
            ->assertStatus(200)
            ->assertSee(now()->isoFormat('YYYY年M月D日(ddd)'))
            ->assertSee(now()->format('H:i'));

        // テスト日時解除（引数なしで呼ぶと解除）
        Carbon::setTestNow();
    }

    // 勤務外
    public function test_status_is_displayed_as_off_work()
    {
        $user = User::factory()->create([
            'status_id' => 1,
        ]);

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance');

        $response
            ->assertStatus(200)
            ->assertSee('勤務外');
    }

    // 出勤中
    public function test_status_is_displayed_as_working()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance');

        $response
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    // 休憩中
    public function test_status_is_displayed_as_break()
    {
        $user = User::factory()->create([
            'status_id' => 3,
        ]);

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance');

        $response
            ->assertStatus(200)
            ->assertSee('休憩中');
    }

    // 退勤済
    public function test_status_is_displayed_as_finished()
    {
    $user = User::factory()->create([
        'status_id' => 4,
    ]);

    AttendanceRecord::factory()->create([
        'user_id' => $user->id,
        'work_date' => now()->format('Y-m-d'),
    ]);

    $response =
        $this
        ->actingAs($user)
        ->get('/attendance');

    $response
        ->assertStatus(200)
        ->assertSee('退勤済');
    }
    // 出勤ボタンが正しく機能するか
    public function test_clock_in_button_works()
    {
        $user = User::factory()->create([
            'status_id' => 1,
        ]);

        // 打刻画面確認
        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤');

        // 出勤処理
        $response =
            $this
            ->actingAs($user)
            ->post('/attendance/clock-in');

        $response
            ->assertRedirect('/attendance');

        // DB再取得
        $user->refresh();

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤中');
    }

    // 退勤済なら出勤ボタン表示されない
    public function test_clock_in_button_is_not_displayed_after_clock_out()
    {
        $user = User::factory()->create([
            'status_id' => 4,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
        ]);

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤済')
            ->assertSee('お疲れ様でした。');
    }
    // 休憩ボタンが正しく機能するか
    public function test_break_start_button_works()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_out' => null,
        ]);

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩入');

        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        $user->refresh();

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩中');
    }

    // 休憩は一日に何回でもできるか
    public function test_break_can_be_taken_multiple_times()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_out' => null,
        ]);

        // 休憩入
        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        // DB更新
        $user->refresh();

        // 休憩戻
        $this
            ->actingAs($user)
            ->post('/attendance/break-end');

        $user->refresh();

        // 再び休憩入ボタン表示
        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩入');
    }

    // 休憩戻ボタンが正しく機能するか
    public function test_break_end_button_works()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_out' => null,
        ]);

        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩戻');

        $this
            ->actingAs($user)
            ->post('/attendance/break-end');

        $user->refresh();

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤中');
    }
    // 休憩戻は一日に何回でもできるか
    public function test_break_end_can_be_done_multiple_times()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_out' => null,
        ]);

        // 1回目
        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        $user->refresh();

        $this
            ->actingAs($user)
            ->post('/attendance/break-end');

        $user->refresh();

        // 2回目
        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        $user->refresh();

        // 再度休憩中なら休憩戻表示
        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩戻');
    }

    // 退勤ボタンが正しく機能するか
    public function test_clock_out_button_works()
    {
        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_out' => null,
        ]);

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤');

        $this
            ->actingAs($user)
            ->post('/attendance/clock-out');

        $user->refresh();

        $this
            ->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤済')
            ->assertSee('お疲れ様でした。');
    }
}
