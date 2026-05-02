<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    // ログイン済みユーザーがログアウトボタンを押したとき、
    // ログアウト処理が実行されることを確認するテスト
    public function test_user_can_logout(): void
    {
        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/logout', [
            'redirect_to' => 'user_login',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
