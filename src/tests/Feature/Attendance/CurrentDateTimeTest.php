<?php

namespace Tests\Feature\Attendance;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    // 現在の日時情報が画面と同じ形式で表示されることを確認するテスト
    public function test_current_datetime_is_displayed_in_ui_format(): void
    {
        // 現在日時を固定
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 12, 00, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        // 画面の日付表示形式に合わせて確認
        $response->assertStatus(200);
        $response->assertSee('2026年4月1日');
        $response->assertSee('12:00');

        Carbon::setTestNow();
    }
}
