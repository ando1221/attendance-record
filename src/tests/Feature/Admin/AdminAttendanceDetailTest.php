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

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 管理者ロールを作成する補助メソッド
    private function createAdminRole(): Role
    {
        return Role::create([
            'code' => 'admin',
            'name' => '管理者',
        ]);
    }

    // 一般ユーザーロールを作成する補助メソッド
    private function createUserRole(): Role
    {
        return Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);
    }

    // 退勤済ステータスを作成する補助メソッド
    private function createFinishedStatus(): AttendanceStatus
    {
        return AttendanceStatus::create([
            'code' => 'finished',
            'name' => '退勤済',
        ]);
    }

    // 管理者ユーザーを作成する補助メソッド
    private function createAdminUser(Role $adminRole): User
    {
        return User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => '管理者ユーザー',
            'email_verified_at' => now(),
        ]);
    }

    // 対象スタッフを作成する補助メソッド
    private function createStaffUser(Role $userRole): User
    {
        return User::factory()->create([
            'role_id' => $userRole->id,
            'name' => '対象スタッフ',
            'email_verified_at' => now(),
        ]);
    }

    // テスト用勤怠を作成する補助メソッド
    private function createAttendanceForUser(User $user, AttendanceStatus $status): Attendance
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $status->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:00:00'),
            'note' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::parse('2026-04-10 12:00:00'),
            'break_end_at' => Carbon::parse('2026-04-10 13:00:00'),
        ]);

        return $attendance;
    }

    // 正常な更新パラメータを返す補助メソッド
    private function validUpdateParams(): array
    {
        return [
            'requested_clock_in_at' => '09:00',
            'requested_clock_out_at' => '18:00',
            'requested_note' => '管理者修正',
            'breaks' => [
                [
                    'break_start_at' => '12:00',
                    'break_end_at' => '13:00',
                ],
            ],
        ];
    }

    // 詳細画面に表示される情報が対象勤怠と一致することを確認するテスト
    public function test_selected_attendance_data_is_displayed_on_admin_detail_page(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = $this->createAttendanceForUser($staff, $finishedStatus);

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('対象スタッフ');
        $response->assertSee('2026年');
        $response->assertSee('4月10日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    // 出勤時間が退勤時間より後の場合、
    // 「出勤時間もしくは退勤時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = $this->createAttendanceForUser($staff, $finishedStatus);

        $params = $this->validUpdateParams();
        $params['requested_clock_in_at'] = '19:00';
        $params['requested_clock_out_at'] = '18:00';

        $response = $this->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), $params);

        $response->assertRedirect(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'requested_clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後の場合、
    // 「休憩時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = $this->createAttendanceForUser($staff, $finishedStatus);

        $params = $this->validUpdateParams();
        $params['breaks'][0]['break_start_at'] = '18:30';
        $params['breaks'][0]['break_end_at'] = '19:00';

        $response = $this->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), $params);

        $response->assertRedirect(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後の場合、
    // 「休憩時間もしくは退勤時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = $this->createAttendanceForUser($staff, $finishedStatus);

        $params = $this->validUpdateParams();
        $params['breaks'][0]['break_start_at'] = '17:30';
        $params['breaks'][0]['break_end_at'] = '18:30';

        $response = $this->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), $params);

        $response->assertRedirect(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考未入力の場合、
    // 「備考を記入してください」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_note_is_empty(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $admin = $this->createAdminUser($adminRole);
        $staff = $this->createStaffUser($userRole);

        $attendance = $this->createAttendanceForUser($staff, $finishedStatus);

        $params = $this->validUpdateParams();
        $params['requested_note'] = '';

        $response = $this->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', ['id' => $attendance->id]), $params);

        $response->assertRedirect(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'requested_note' => '備考を記入してください',
        ]);
    }
}
