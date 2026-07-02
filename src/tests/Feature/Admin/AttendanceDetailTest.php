<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\StatusesTableSeeder;
use Database\Seeders\RequestStatusesTableSeeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;

class AttendanceDetailTest extends TestCase
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

    public function test_admin_can_see_selected_attendance_detail()
    {
        // 管理者作成
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // 一般ユーザー作成
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        // 勤怠作成
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-06-27',
            'clock_in' => '2026-06-27 09:00:00',
            'clock_out' => '2026-06-27 18:00:00',
        ]);

        // 休憩作成
        BreakTime::create([
            'attendance_record_id' => $attendance->id,
            'break_start' => '2026-06-27 12:00:00',
            'break_end' => '2026-06-27 13:00:00',
        ]);

        // ログイン
        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);

        // 表示確認
        // 名前
        $response->assertSee('山田太郎');
        // 日付（Blade表示形式）
        $response
            ->assertSee('2026年')
            ->assertSee('6月27日');
        // 出勤退勤
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 休憩
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        // 修正ボタン表示
        $response->assertSee('修正');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_clock_in_cannot_be_after_clock_out()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create();

        // Arrange
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        // Act
        $response = $this
            ->actingAs($admin)
            ->from('/admin/attendance/' . $attendance->id)
            ->post(
            '/admin/attendance/' . $attendance->id,
            [
                'attendance_id' => $attendance->id,
                'work_date' => now()->format('Y-m-d'),
                'requested_clock_in' => '18:00',
                'requested_clock_out' => '09:00',

                'breaks' => [
                    [
                        'start' => null,
                        'end' => null,
                    ]
                ],

                'reason' => 'テスト',
            ]
        );

        // Assert
        $response->assertRedirect(
            '/admin/attendance/' . $attendance->id
        );

        // バリデーション検証
        $response->assertSessionHasErrors([
            'requested_clock_out' =>
            '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }
    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_start_cannot_be_after_clock_out()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // Arrange
        $user = User::factory()->create();

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($admin)
            ->from('/admin/attendance/' . $attendance->id)
            ->post('/admin/attendance/' . $attendance->id,
                [
                'attendance_id' => $attendance->id,
                'work_date' => now()->format('Y-m-d'),

                'requested_clock_in' => '06:00',
                'requested_clock_out' => '09:00',

                'breaks' => [
                    [
                        'start' => '10:00',
                        'end' => null,
                    ]
                ],

                'reason' => 'テスト',
            ]
        );

        // Assert
        $response->assertRedirect(
            '/admin/attendance/' . $attendance->id
        );

        // バリデーション検証
        $response->assertSessionHasErrors([
            'breaks.0.start' =>
            '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合
    public function test_break_end_cannot_be_after_clock_out()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // Arrange
        $user = User::factory()->create();

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($admin)
            ->from('/admin/attendance/' . $attendance->id)
            ->post(
                '/admin/attendance/' . $attendance->id,
                [
                    'attendance_id' => $attendance->id,
                    'work_date' => now()->format('Y-m-d'),

                    'requested_clock_in' => '06:00',
                    'requested_clock_out' => '09:00',

                    'breaks' => [
                        [
                            'start' => '08:30',
                            'end' => '09:30',
                        ]
                    ],

                    'reason' => 'テスト',
                ]
            );

        // Assert
        $response->assertRedirect(
            '/admin/attendance/' . $attendance->id
        );

        // バリデーション検証
        $response->assertSessionHasErrors([
            'breaks.0.end' =>
            '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考未入力の場合、エラーメッセージが表示される
    public function test_reason_is_required()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // Arrange
        $user = User::factory()->create();

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($admin)
            ->from(
                '/admin/attendance/' . $attendance->id)
            ->post(
                '/admin/attendance/' . $attendance->id,
                [
                'attendance_id' => $attendance->id,
                'work_date' => '2026-06-25',

                'requested_clock_in' => '06:00',
                'requested_clock_out' => '09:00',

                'breaks' => [
                    [
                        'start' => '08:00',
                        'end' => '08:30',
                    ]
                ],

                'reason' => '',
                ]
        );

        // Assert
        $response->assertRedirect(
            '/admin/attendance/' . $attendance->id
        );

        $response->assertSessionHasErrors([
            'reason' =>
            '備考を記入してください',
        ]);
    }
}
