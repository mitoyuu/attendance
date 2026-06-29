<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Database\Seeders\StatusesTableSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
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
            StatusesTableSeeder::class
        );
    }
    // 会員登録後認証メールが送信される
    public function test_verification_email_is_sent_after_register()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }
    // メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_user_can_access_email_verification_notice()
    {
        $user = User::factory()->create([
            'status_id' => 1,
            'email_verified_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/email/verify');

        $response->assertStatus(200);

        $response->assertSee(
            '認証はこちらから'
        );
        $response
            ->assertSee(
                'https://mailtrap.io/sandboxes',
                false
            );
    }

    // メール認証サイトのメール認証を完了すると勤怠登録画面に遷移する
    public function test_verified_user_is_redirected_to_attendance()
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1(
                    $user->email
                ),
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get($url);

        $response
            ->assertRedirect(
            '/attendance?verified=1'
        );

        $this->assertNotNull(
            $user->fresh()
                ->email_verified_at
        );

        Event::assertDispatched(
            Verified::class
        );
    }
}
