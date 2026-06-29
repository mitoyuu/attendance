<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
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

    // 名前未入力
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        // バリデーション検証
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }
    // メールアドレス未入力
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'aaa',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        // バリデーション検証
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワード未入力
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'aaa',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);
        // バリデーション検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }
    // パスワード８文字未満の場合
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => 'aaa',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        // バリデーション検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }
    // パスワード不一致の場合
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => 'aaa',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => 'password',
        ]);
        // バリデーション検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    // 正常に会員登録できたか
    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertRedirect(
            '/attendance'
        );

        // DBに保存されたか確認
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }

}
