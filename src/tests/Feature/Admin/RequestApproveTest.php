<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use Database\Seeders\StatusesTableSeeder;
use Database\Seeders\RequestStatusesTableSeeder;

class RequestApproveTest extends TestCase
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

    // 承認待ちの修正申請が全て表示されている
    public function test_pending_requests_are_displayed()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // 承認待ちユーザー
        $pendingUser1 = User::factory()->create();
        $pendingUser2 = User::factory()->create();

        // 承認済みユーザー
        $approvedUser = User::factory()->create();

        // 承認待ち用勤怠①
        $pendingAttendance1 = AttendanceRecord::factory()->create([
            'user_id' => $pendingUser1->id,
        ]);
        // 承認待ち用勤怠②
        $pendingAttendance2 = AttendanceRecord::factory()->create([
            'user_id' => $pendingUser2->id,
        ]);

        // 承認済み用勤怠
        $approvedAttendance = AttendanceRecord::factory()->create([
            'user_id' => $approvedUser->id,
        ]);

        // 承認待ち①
        StampCorrectionRequest::create([
            'attendance_record_id' => $pendingAttendance1->id,
            'request_status_id' => 1,
            'reason' => '承認待ち1',
        ]);
        // 承認待ち②
        StampCorrectionRequest::create([
            'attendance_record_id' => $pendingAttendance2->id,
            'request_status_id' => 1,
            'reason' => '承認待ち2',
        ]);

        // 承認済み
        StampCorrectionRequest::create([
            'attendance_record_id' => $approvedAttendance->id,
            'request_status_id' => 2,
            'reason' => '承認済み',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);

        $response->assertSee($pendingUser1->name);

        $response->assertSee($pendingUser2->name);

        $response->assertDontSee($approvedUser->name);
    }

    // 承認済みの修正申請が全て表示されている
    public function test_approved_requests_are_displayed()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // 承認待ちユーザー
        $pendingUser = User::factory()->create();

        // 承認済みユーザー
        $approvedUser1 = User::factory()->create();
        $approvedUser2 = User::factory()->create();


        // 承認待ち用勤怠
        $pendingAttendance = AttendanceRecord::factory()->create([
            'user_id' => $pendingUser->id,
        ]);

        // 承認済み用勤怠
        $approvedAttendance1 = AttendanceRecord::factory()->create([
            'user_id' => $approvedUser1->id,
        ]);
        $approvedAttendance2 = AttendanceRecord::factory()->create([
            'user_id' => $approvedUser2->id,
        ]);

        // 承認待ち
        StampCorrectionRequest::create([
            'attendance_record_id' => $pendingAttendance->id,
            'request_status_id' => 1,
            'reason' => '承認待ち',
        ]);

        // 承認済み①
        StampCorrectionRequest::create([
            'attendance_record_id' => $approvedAttendance1->id,
            'request_status_id' => 2,
            'reason' => '承認済み',
        ]);
        // 承認済み②
        StampCorrectionRequest::create([
            'attendance_record_id' => $approvedAttendance2->id,
            'request_status_id' => 2,
            'reason' => '承認済み',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);

        $response->assertSee($approvedUser1->name);
        $response->assertSee($approvedUser2->name);

        $response->assertDontSee($pendingUser->name);
    }

    // 修正申請の詳細内容が正しく表示されているか
    public function test_request_detail_is_displayed()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // ユーザー
        $user = User::factory()->create();
        // 他人
        $otherUser = User::factory()->create();

        // 承認待ち用勤怠
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);
        // 他人の勤怠
        $otherAttendance = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // 承認待ち
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'request_status_id' => 1,
            'reason' => '承認待ちテスト',
            'requested_clock_in' => '2026-06-15 09:00',
            'requested_clock_out' => '2026-06-15 18:00',
        ]);

        // 他人の承認待ち
        StampCorrectionRequest::create([
            'attendance_record_id' => $otherAttendance->id,
            'request_status_id' => 1,
            'reason' => '承認待ち',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/stamp_correction_request/approve/' . $attendance->id);

        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('承認待ちテスト');

        $response->assertDontSee($otherUser->name);
    }


    // 修正申請の承認処理が正しく行われる
    public function test_request_can_be_approved()
    {
        // 管理者
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        // ユーザー
        $user = User::factory()->create();

        // 勤怠
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-06-15 08:00:00',
            'clock_out' => '2026-06-15 17:00:00',
        ]);

        // 修正申請
        $request = StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'request_status_id' => 1,
            'requested_clock_in' => '2026-06-15 09:00',
            'requested_clock_out' => '2026-06-15 18:00',
            'reason' => '修正理由',
        ]);

        // 承認実行
        $response = $this
            ->actingAs($admin)
            ->post(
                '/admin/stamp_correction_request/approve/' . $request->id
            );

        // 承認されたか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'id' => $request->id,
                'request_status_id' => 2,
            ]
        );

        // 勤怠が更新されたか
        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $attendance->id,
                'clock_in' => '2026-06-15 09:00:00',
                'clock_out' => '2026-06-15 18:00:00',
            ]
        );

        $response->assertRedirect(
            '/admin/stamp_correction_request/approve/'
                . $attendance->id        );
    }
}
