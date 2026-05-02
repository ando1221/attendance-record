<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレス未入力でログインしたとき、
    // 「メールアドレスを入力してください」が表示されることを確認するテスト
    public function test_email_is_required(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワード未入力でログインしたとき、
    // 「パスワードを入力してください」が表示されることを確認するテスト
    public function test_password_is_required(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録されていないログイン情報でログインしたとき、
    // 「ログイン情報が登録されていません」が表示されることを確認するテスト
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'no-user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 正しいログイン情報でログインしたとき、
    // 勤怠登録画面へ遷移することを確認するテスト
    public function test_user_can_login(): void
    {
        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('attendance.show'));
    }
}
