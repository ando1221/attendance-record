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

class AdminStaffTest extends TestCase
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

    // 管理者ユーザーを作成
    private function createAdminUser(Role $adminRole): User
    {
        return User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);
    }

    // 一般ユーザーを作成
    private function createStaffUser(Role $userRole, string $name, string $email): User
    {
        return User::factory()->create([
            'role_id' => $userRole->id,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
        ]);
    }

    // テスト用勤怠を作成
    private function createAttendanceForUser(
        User $user,
        AttendanceStatus $status,
        string $workDate,
        string $clockIn,
        string $clockOut,
        ?array $break = null
    ): Attendance {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $status->id,
            'work_date' => $workDate,
            'clock_in_at' => Carbon::parse($workDate . ' ' . $clockIn),
            'clock_out_at' => Carbon::parse($workDate . ' ' . $clockOut),
            'note' => null,
        ]);

        if ($break) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => Carbon::parse($workDate . ' ' . $break['start']),
                'break_end_at' => Carbon::parse($workDate . ' ' . $break['end']),
            ]);
        }

        return $attendance;
    }

    // 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できることを確認するテスト
    public function test_admin_can_view_all_staff_names_and_emails(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();

        $admin = $this->createAdminUser($adminRole);

        $staff1 = $this->createStaffUser($userRole, '山田太郎', 'yamada@example.com');
        $staff2 = $this->createStaffUser($userRole, '佐藤花子', 'sato@example.com');

        $response = $this->actingAs($admin)->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($staff1->email);
        $response->assertSee($staff2->name);
        $response->assertSee($staff2->email);
    }

    // 選択したユーザーの勤怠情報が正しく表示されることを確認するテスト
    public function test_selected_staff_attendance_is_displayed_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole, '対象スタッフ', 'staff@example.com');

        $this->createAttendanceForUser(
            $staff,
            $finishedStatus,
            '2026-04-10',
            '09:00',
            '18:00',
            ['start' => '12:00', 'end' => '13:00']
        );

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertSee('対象スタッフ');
        $response->assertSee('04/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');

        Carbon::setTestNow();
    }

    // 前月を押下したときに前月の情報が表示されることを確認するテスト
    public function test_previous_month_information_is_displayed_on_staff_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole, '前月スタッフ', 'before@example.com');

        $this->createAttendanceForUser(
            $staff,
            $finishedStatus,
            '2026-03-15',
            '09:00',
            '18:00'
        );

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/03');
        $response->assertSee('03/15');

        Carbon::setTestNow();
    }

    // 翌月を押下したときに翌月の情報が表示されることを確認するテスト
    public function test_next_month_information_is_displayed_on_staff_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole, '翌月スタッフ', 'next@example.com');

        $this->createAttendanceForUser(
            $staff,
            $finishedStatus,
            '2026-05-12',
            '09:00',
            '18:00'
        );

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'id' => $staff->id,
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/05');
        $response->assertSee('05/12');

        Carbon::setTestNow();
    }

    // 詳細を押下するとその日の勤怠詳細画面に遷移できることを確認するテスト
    public function test_admin_can_move_to_staff_attendance_detail_page(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole, '詳細スタッフ', 'detail@example.com');

        $attendance = $this->createAttendanceForUser(
            $staff,
            $finishedStatus,
            '2026-04-10',
            '09:00',
            '18:00'
        );

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('詳細スタッフ');
        $response->assertSee('2026年');
        $response->assertSee('4月10日');
    }
}
