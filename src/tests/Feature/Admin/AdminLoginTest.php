<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレス未入力で管理者ログインしたとき、
    // 「メールアドレスを入力してください」が表示されることを確認するテスト
    public function test_email_is_required(): void
    {
        $adminRole = Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);

        User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワード未入力で管理者ログインしたとき、
    // 「パスワードを入力してください」が表示されることを確認するテスト
    public function test_password_is_required(): void
    {
        $adminRole = Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);

        User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録内容と一致しない情報で管理者ログインしたとき、
    // 「ログイン情報が登録されていません」が表示されることを確認するテスト
    public function test_login_fails_with_invalid_credentials(): void
    {
        $adminRole = Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);

        User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 一般ユーザーで管理者ログインを試みたとき、
    // 管理者ログインできずエラーになることを確認するテスト
    public function test_general_user_cannot_login_from_admin_login(): void
    {
        $userRole = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        User::factory()->create([
            'role_id' => $userRole->id,
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest();
    }

    // 正しい管理者情報でログインしたとき、
    // 管理者勤怠一覧画面へ遷移することを確認するテスト
    public function test_admin_can_login(): void
    {
        $adminRole = Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.attendance.list'));
    }
}
