<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 勤務外ステータスを作成
    private function createOffDutyStatus(): AttendanceStatus
    {
        return AttendanceStatus::create([
            'code' => 'off_duty',
            'name' => '勤務外',
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

    // 一般ユーザーロールを作成
    private function createUserRole(): Role
    {
        return Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);
    }

    // 自分が行った勤怠情報がすべて表示されることを確認するテスト
    public function test_all_of_users_attendance_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:00:00'),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => Carbon::parse('2026-04-10 12:00:00'),
            'break_end_at' => Carbon::parse('2026-04-10 13:00:00'),
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-20',
            'clock_in_at' => Carbon::parse('2026-04-20 10:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-20 19:00:00'),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => Carbon::parse('2026-04-20 14:00:00'),
            'break_end_at' => Carbon::parse('2026-04-20 14:30:00'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertSee('04/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');

        $response->assertSee('04/20');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('00:30');
        $response->assertSee('08:30');

        Carbon::setTestNow();
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示されることを確認するテスト
    public function test_current_month_is_displayed_when_opening_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = $this->createUserRole();
        $this->createOffDutyStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2026/04');

        Carbon::setTestNow();
    }

    // 前月の情報が表示されることを確認するテスト
    public function test_previous_month_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::parse('2026-03-15 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-03-15 18:00:00'),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/03');
        $response->assertSee('03/15');

        Carbon::setTestNow();
    }

    // 翌月の情報が表示されることを確認するテスト
    public function test_next_month_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-05-12',
            'clock_in_at' => Carbon::parse('2026-05-12 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-05-12 18:00:00'),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/05');
        $response->assertSee('05/12');

        Carbon::setTestNow();
    }

    // 詳細を押下するとその日の勤怠詳細画面へ遷移できることを確認するテスト
    public function test_user_can_move_to_attendance_detail_page(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:00:00'),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');

        Carbon::setTestNow();
    }
}
