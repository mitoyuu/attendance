<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use Database\Seeders\StatusesTableSeeder;
use Database\Seeders\RequestStatusesTableSeeder;

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
            \Database\Seeders\StatusesTableSeeder::class,
            \Database\Seeders\RequestStatusesTableSeeder::class,
        ]);
    }

    // 勤怠詳細画面に勤怠情報が表示されるか
    public function test_can_view_attendance_details()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-06-25',
            'clock_in' => '2026-06-25 09:00:00',
            'clock_out' => '2026-06-25 18:00:00',
        ]);

        // ログイン
        $this->actingAs($user);

        // Act
        $response = $this->get(
            '/attendance/detail/' . $attendance->id
        );

        // Assert
        $response->assertStatus(200);

        // 名前
        $response->assertSee('山田太郎');

        // 日付
        $response
            ->assertSee('2026年')
            ->assertSee('6月25日');

        // 出勤
        $response->assertSee('value="09:00"', false);

        // 退勤
        $response->assertSee('value="18:00"', false);
    }

    // 休憩時間が表示されるか
    public function test_break_time_is_displayed()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $attendance->breakTimes()->create([
            'break_start' => '2026-06-25 12:00:00',
            'break_end' => '2026-06-25 13:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            '/attendance/detail/' . $attendance->id
        );

        $response->assertSee('value="12:00"', false);

        $response->assertSee('value="13:00"', false);
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_clock_in_cannot_be_after_clock_out()
    {
        // 過去に output buffer risked 回避で ob_end_clean を入れていたが、
        // AttendanceDetailTest の POST + Validation テストで副作用発生。
        // 現状は削除して正常動作。

        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->from(
            '/attendance/detail/' . $attendance->id
        )->post(
            '/attendance/detail/' . $attendance->id,
            [
                'attendance_id' => $attendance->id,
                'work_date' => '2026-06-25',

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
            '/attendance/detail/' . $attendance->id
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
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->from(
            '/attendance/detail/' . $attendance->id
        )->post(
            '/attendance/detail/' . $attendance->id,
            [
                'attendance_id' => $attendance->id,
                'work_date' => '2026-06-25',

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
            '/attendance/detail/' . $attendance->id
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
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->from(
            '/attendance/detail/' . $attendance->id
        )->post(
            '/attendance/detail/' . $attendance->id,
            [
                'attendance_id' => $attendance->id,
                'work_date' => '2026-06-25',

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
            '/attendance/detail/' . $attendance->id
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
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->from(
            '/attendance/detail/' . $attendance->id
        )->post(
            '/attendance/detail/' . $attendance->id,
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
            '/attendance/detail/' . $attendance->id
        );

        $response->assertSessionHasErrors([
            'reason' =>
            '備考を記入してください',
        ]);
    }

    // 修正申請が保存される
    public function test_attendance_correction_request_is_created()
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->post(
            '/attendance/detail/' . $attendance->id,
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

                'reason' => '修正申請テスト',
            ]
        );

        // Assert
        $response->assertRedirect(
            '/attendance/detail/' . $attendance->id
        );

        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_record_id' =>
                $attendance->id,

                'reason' =>
                '修正申請テスト',

                'request_status_id' => 1,
            ]
        );
    }
    // 承認待ち一覧に自分の申請が表示される
    public function test_pending_requests_are_displayed()
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        // 承認待ち申請作成
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'requested_clock_in' => '2026-06-25 09:00:00',
            'requested_clock_out' => '2026-06-25 18:00:00',
            'reason' => '修正申請テスト',
            'request_status_id' => 1, // 承認待ち
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->get(
            '/stamp_correction_request/list'
        );

        // Assert
        $response->assertStatus(200);

        $response->assertSee(
            '修正申請テスト'
        );
    }
    // 承認済み一覧に承認済み申請が表示される
    public function test_approved_requests_are_displayed()
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        StampCorrectionRequest::create([
            'attendance_record_id' =>
            $attendance->id,

            'requested_clock_in' =>
            '2026-06-25 09:00:00',

            'requested_clock_out' =>
            '2026-06-25 18:00:00',

            'reason' =>
            '承認済み申請テスト',

            'request_status_id' => 2,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->get(
            '/stamp_correction_request/list?tab=approved');

        // Assert
        $response->assertStatus(200);

        $response->assertSee(
            '承認済み申請テスト'
        );
    }

    // 詳細を押下すると勤怠詳細画面へ遷移できる
    public function test_can_move_to_attendance_detail_from_request_list()
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'requested_clock_in' => '2026-06-25 09:00:00',
            'requested_clock_out' => '2026-06-25 18:00:00',
            'reason' => '詳細遷移テスト',
            'request_status_id' => 1,
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->get(
            '/stamp_correction_request/list'
        );

        // Assert
        $response->assertStatus(200);

        $response->assertSee(
            '/attendance/detail/' . $attendance->id,
            false
        );
    }
}
