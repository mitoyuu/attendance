<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
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

        $this->seed(
            \Database\Seeders\StatusesTableSeeder::class
        );
    }
    // 出勤時刻が勤怠一覧画面で確認できるか
    public function test_clock_in_time_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-23 09:30:00')
        );

        $user = User::factory()->create([
            'status_id' => 1,
        ]);

        // 出勤
        $this
            ->actingAs($user)
            ->post('/attendance/clock-in');

        // 一覧確認
        $response =
            $this
            ->actingAs($user)
            ->get('/attendance/list');

        $response
            ->assertStatus(200)
            ->assertSee('09:30');

        Carbon::setTestNow();
    }

    // 休憩時刻が勤怠一覧画面で確認できるか
    public function test_break_total_time_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-23 09:00:00')
        );

        $user = User::factory()->create([
            'status_id' => 2,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'clock_in' => now(),
            'clock_out' => now()->copy()->addHours(8),
        ]);

        // 休憩開始
        $this
            ->actingAs($user)
            ->post('/attendance/break-start');

        // 30分経過
        Carbon::setTestNow(
            Carbon::parse('2026-06-23 09:30:00')
        );

        // 一旦ステータス更新
        $user->refresh();

        // 休憩終了できる状態にする
        $user->status_id = 3;
        $user->save();

        $this
            ->actingAs($user)
            ->post('/attendance/break-end');

        // 休憩戻完了後
        $attendance = AttendanceRecord::first();

        $attendance->update([
            'clock_out' =>
            Carbon::now()
        ]);

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance/list');

        $response
            ->assertSee('0:30');

        Carbon::setTestNow();
    }

    // 退勤時刻が一覧画面で確認できるか
    public function test_clock_out_time_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-06-23 09:30:00'
            )
        );

        $user = User::factory()->create([
            'status_id' => 1,
        ]);

        $this
            ->actingAs($user)
            ->post('/attendance/clock-in');

        Carbon::setTestNow(
            Carbon::parse(
                '2026-06-23 17:30:00'
            )
        );

        $user->refresh();

        $this
            ->actingAs($user)
            ->post('/attendance/clock-out');

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance/list');

        $response
            ->assertSee('17:30');

        Carbon::setTestNow();
    }

    // 現在月表示(画面状態確認)
    public function test_current_month_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-06-24'
            )
        );

        $user =
            User::factory()
            ->create();

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance/list');

        $response
            ->assertSee('2026/06');

        Carbon::setTestNow();
    }

    // 自分の勤怠情報が全て表示(データ表示確認)
    public function test_user_attendance_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-24')
        );

        $user =
            User::factory()
            ->create();

        $otherUser =
            User::factory()
            ->create();

        $attendance =
            AttendanceRecord::factory()
            ->create([
                'user_id' => $user->id,
                'work_date' => '2026-06-10',
                'clock_in' => '2026-06-10 09:00:00',
                'clock_out' => '2026-06-10 18:00:00',
            ]);
        BreakTime::create([
            'attendance_record_id' => $attendance->id,
            'break_start' => '2026-06-10 12:00:00',
            'break_end' => '2026-06-10 12:30:00',
        ]);
        AttendanceRecord::factory()
            ->create([
                'user_id' => $user->id,
                'work_date' => '2026-06-20',
                'clock_in' => '2026-06-20 10:00:00',
                'clock_out' => '2026-06-20 19:00:00',
            ]);
        // 他人
        AttendanceRecord::factory()
            ->create([
                'user_id' => $otherUser->id,
                'work_date' => '2026-06-15',
                'clock_in' => '2026-06-15 08:00:00',
                'clock_out' => '2026-06-15 17:00:00',
            ]);

        $response =
            $this
            ->actingAs($user)
            ->get('/attendance/list');

        $response
            ->assertSee('09:00')
            ->assertSee('18:00')
            // 休憩合計時間
            ->assertSee('0:30')
            // 勤務合計時間
            ->assertSee('8:30')
            ->assertSee('10:00')
            ->assertSee('19:00')

            ->assertDontSee('08:00')
            ->assertDontSee('17:00');

        Carbon::setTestNow();
    }

    // 前月(画面状態確認)
    public function test_previous_month_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-24')
        );

        $user =
            User::factory()
            ->create();

        $response =
            $this
            ->actingAs($user)
            ->get(
                '/attendance/list?month=2026-05'
            );

        $response
            ->assertSee('2026/05');

        Carbon::setTestNow();
    }

    // 翌月(画面状態確認)
    public function test_next_month_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-24')
        );

        $user =
            User::factory()
            ->create();

        $response =
            $this
            ->actingAs($user)
            ->get(
                '/attendance/list?month=2026-07'
            );

        $response
            ->assertSee('2026/07');

        Carbon::setTestNow();
    }

    // 詳細押下(遷移確認)
    public function test_can_view_attendance_detail_page()
    {
        $user =
            User::factory()
            ->create();

        $attendance =
            AttendanceRecord::factory()
            ->create([
                'user_id' => $user->id,
            ]);

        $response =
            $this
            ->actingAs($user)
            ->get(
                '/attendance/detail/'
                    . $attendance->id
            );

        // 4. 詳細ボタンを押して勤怠詳細画面に遷移する
        $response->assertStatus(200);
        $response
            ->assertViewIs(
                'attendance.detail'
            );

        $response
            ->assertViewHas(
                'attendance',
                function ($viewAttendance)
                use ($attendance) {

                    return
                        $viewAttendance->id
                        ===
                        $attendance->id;
                }
            );
    }
}
