<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 管理者ロールを作成
    private function createAdminRole(): Role
    {
        return Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);
    }

    // 一般ユーザーロールを作成
    private function createUserRole(): Role
    {
        return Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);
    }

    // 退勤済ステータスを作成
    private function createFinishedStatus(): AttendanceStatus
    {
        return AttendanceStatus::create([
            'code' => 'finished',
            'name' => '退勤済',
        ]);
    }

    // 管理者を作成
    private function createAdminUser(Role $adminRole): User
    {
        return User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => '管理者ユーザー',
            'email_verified_at' => now(),
        ]);
    }

    // その日になされた全ユーザーの勤怠情報が正確に確認できることを確認するテスト
    public function test_admin_can_view_all_users_attendance_for_the_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);

        $user1 = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => '山田太郎',
            'email_verified_at' => now(),
        ]);

        $user2 = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => '佐藤花子',
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-26',
            'clock_in_at' => Carbon::parse('2026-04-26 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-26 18:00:00'),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => Carbon::parse('2026-04-26 12:00:00'),
            'break_end_at' => Carbon::parse('2026-04-26 13:00:00'),
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-26',
            'clock_in_at' => Carbon::parse('2026-04-26 10:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-26 19:00:00'),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => Carbon::parse('2026-04-26 14:00:00'),
            'break_end_at' => Carbon::parse('2026-04-26 14:30:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.list', [
            'date' => '2026-04-26',
        ]));

        $response->assertStatus(200);

        // 1人目の勤怠情報
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');

        // 2人目の勤怠情報
        $response->assertSee('佐藤花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('00:30');
        $response->assertSee('08:30');

        Carbon::setTestNow();
    }

    // 遷移時に現在の日付が表示されることを確認するテスト
    public function test_current_date_is_displayed_when_opening_admin_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $admin = $this->createAdminUser($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2026/04/26');

        Carbon::setTestNow();
    }

    // 前日を表示したときに前日の勤怠情報が表示されることを確認するテスト
    public function test_previous_day_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);

        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => '前日ユーザー',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-25',
            'clock_in_at' => Carbon::parse('2026-04-25 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-25 18:00:00'),
            'note' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.list', [
            'date' => '2026-04-25',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/04/25');
        $response->assertSee('前日ユーザー');

        Carbon::setTestNow();
    }

    // 翌日を表示したときに翌日の勤怠情報が表示されることを確認するテスト
    public function test_next_day_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);

        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => '翌日ユーザー',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-27',
            'clock_in_at' => Carbon::parse('2026-04-27 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-27 18:00:00'),
            'note' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.list', [
            'date' => '2026-04-27',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/04/27');
        $response->assertSee('翌日ユーザー');

        Carbon::setTestNow();
    }
}
