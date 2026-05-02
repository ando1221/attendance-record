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

class AdminAttendanceCsvTest extends TestCase
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

    // 管理者を作成
    private function createAdminUser(Role $adminRole): User
    {
        return User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => '管理者ユーザー',
            'email_verified_at' => now(),
        ]);
    }

    // スタッフを作成
    private function createStaffUser(Role $userRole): User
    {
        return User::factory()->create([
            'role_id' => $userRole->id,
            'name' => 'CSV対象スタッフ',
            'email_verified_at' => now(),
        ]);
    }

    // CSVが正常に出力されることを確認するテスト
    public function test_admin_can_export_staff_attendance_csv(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $this->createOffDutyStatus();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:00:00'),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::parse('2026-04-10 12:00:00'),
            'break_end_at' => Carbon::parse('2026-04-10 13:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff.csv', [
            'id' => $staff->id,
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="' . $staff->name . '_2026-04_attendance.csv"'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $content);
        $this->assertStringContainsString('2026/04/10（金）,09:00,18:00,01:00,08:00', $content);

        Carbon::setTestNow();
    }

    // 勤務外日は00:00でCSV出力されることを確認するテスト
    public function test_off_duty_day_is_exported_with_zero_times_in_csv(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $this->createOffDutyStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff.csv', [
            'id' => $staff->id,
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('2026/04/01（水）,,,00:00,00:00', $content);

        Carbon::setTestNow();
    }
}
