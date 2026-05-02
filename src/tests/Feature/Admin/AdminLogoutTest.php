<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogoutTest extends TestCase
{
    use RefreshDatabase;

    // 管理者がログアウトできることを確認するテスト
    public function test_admin_can_logout(): void
    {
        $adminRole = Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $response = $this->actingAs($admin)->post('/logout', [
            'redirect_to' => 'admin_login',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
    }
}
