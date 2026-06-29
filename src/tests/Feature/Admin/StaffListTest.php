<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\StatusesTableSeeder;
use Database\Seeders\RequestStatusesTableSeeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            StatusesTableSeeder::class,
            RequestStatusesTableSeeder::class,
        ]);
    }
    // 管理者がスタッフ一覧で全一般ユーザーの氏名・メールアドレスを確認できる
    public function test_admin_can_view_user_list()
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        User::factory()->create([
            'name' => '田中太郎',
            'email' => 'tanaka@test.com',
        ]);

        User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'sato@test.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertSee('田中太郎');
        $response->assertSee('tanaka@test.com');

        $response->assertSee('佐藤花子');
        $response->assertSee('sato@test.com');
    }

    // 選択ユーザーの勤怠一覧表示
    public function test_selected_user_attendance_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-27')
        );

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        // 対象ユーザー
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-06-10',
            'clock_in' => '2026-06-10 09:00:00',
            'clock_out' => '2026-06-10 18:00:00',
        ]);

        // 他ユーザー（表示されない想定）
        AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-06-15',
            'clock_in' => '2026-06-15 08:00:00',
            'clock_out' => '2026-06-15 17:00:00',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);

        // 対象ユーザー
        $response->assertSee($user->name);
        // 月ヘッダー確認
        $response->assertSee('2026/06');
        // 一覧データ確認
        $response->assertSee('06/10');

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 他ユーザーは出ない
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');

        Carbon::setTestNow();
    }

    // 前月押下
    public function test_previous_month_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-27')
        );

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'
                    . $user->id
                    . '?month=2026-05'
            );

        $response
            ->assertStatus(200)
            ->assertSee('2026/05');

        Carbon::setTestNow();
    }

    // 翌月
    public function test_next_month_button_displays_previous_month()
    {
        Carbon::setTestNow(
            Carbon::parse('2026-06-27')
        );

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'
                    . $user->id
                    . '?month=2026-07'
            );

        $response
            ->assertStatus(200)
            ->assertSee('2026/07');

        Carbon::setTestNow();
    }

    // 詳細押下でその日の勤怠詳細へ遷移
    public function test_can_move_to_attendance_detail()
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '田中太郎',
        ]);

        $attendance =
            AttendanceRecord::factory()
            ->create([
                'user_id' => $user->id,
                'work_date' => '2026-06-10',
                'clock_in' => '2026-06-10 09:00:00',
                'clock_out' => '2026-06-10 18:00:00',
            ]);

        $response =
            $this
            ->actingAs($admin)
            ->get(
                '/admin/attendance/'
                    . $attendance->id
            );

        $response
            ->assertStatus(200);

        // 誰の勤怠か
        $response->assertSee('田中太郎');

        // どの日か
        $response->assertSee('2026年6月10日');

        // その日の勤怠か
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}