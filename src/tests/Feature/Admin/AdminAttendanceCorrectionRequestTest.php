<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\AttendanceCorrectionRequestStatus;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceCorrectionRequestTest extends TestCase
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

    // 勤怠ステータスを作成
    private function createAttendanceStatuses(): array
    {
        $offDuty = AttendanceStatus::create([
            'code' => 'off_duty',
            'name' => '勤務外',
        ]);

        $working = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
        ]);

        $onBreak = AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
        ]);

        $finished = AttendanceStatus::create([
            'code' => 'finished',
            'name' => '退勤済',
        ]);

        return [$offDuty, $working, $onBreak, $finished];
    }

    // 修正申請ステータスを作成
    private function createRequestStatuses(): array
    {
        $pending = AttendanceCorrectionRequestStatus::create([
            'code' => 'pending',
            'name' => '承認待ち',
        ]);

        $approved = AttendanceCorrectionRequestStatus::create([
            'code' => 'approved',
            'name' => '承認済み',
        ]);

        return [$pending, $approved];
    }

    // 管理者ユーザーを作成
    private function createAdminUser(Role $adminRole): User
    {
        return User::factory()->create([
            'role_id' => $adminRole->id,
            'name' => '管理者ユーザー',
            'email_verified_at' => now(),
        ]);
    }

    // 一般ユーザーを作成
    private function createStaffUser(Role $userRole, string $name): User
    {
        return User::factory()->create([
            'role_id' => $userRole->id,
            'name' => $name,
            'email_verified_at' => now(),
        ]);
    }

    // 勤怠を作成
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

    // 修正申請を作成
    private function createCorrectionRequest(
        Attendance $attendance,
        User $user,
        AttendanceCorrectionRequestStatus $status,
        string $note = '修正申請メモ'
    ): AttendanceCorrectionRequest {
        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status_id' => $status->id,
            'requested_clock_in_at' => '2026-04-10 09:00:00',
            'requested_clock_out_at' => '2026-04-10 18:30:00',
            'requested_note' => $note,
        ]);

        AttendanceCorrectionRequestBreak::create([
            'attendance_correction_request_id' => $request->id,
            'break_start_at' => '2026-04-10 12:00:00',
            'break_end_at' => '2026-04-10 13:00:00',
        ]);

        return $request;
    }

    // 承認待ちの修正申請が全て表示されることを確認するテスト
    public function test_pending_correction_requests_are_displayed(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        [,,, $finishedStatus] = $this->createAttendanceStatuses();
        [$pendingStatus, $approvedStatus] = $this->createRequestStatuses();

        $admin = $this->createAdminUser($adminRole);

        $user1 = $this->createStaffUser($userRole, '申請者A');
        $user2 = $this->createStaffUser($userRole, '申請者B');

        $attendance1 = $this->createAttendanceForUser($user1, $finishedStatus);
        $attendance2 = $this->createAttendanceForUser($user2, $finishedStatus);

        $this->createCorrectionRequest($attendance1, $user1, $pendingStatus, '承認待ち申請A');
        $this->createCorrectionRequest($attendance2, $user2, $pendingStatus, '承認待ち申請B');
        $this->createCorrectionRequest($attendance2, $user2, $approvedStatus, '承認済申請');

        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.list', [
            'status' => 'pending',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請者A');
        $response->assertSee('申請者B');
        $response->assertSee('承認待ち申請A');
        $response->assertSee('承認待ち申請B');
        $response->assertDontSee('承認済申請');
    }

    // 承認済みの修正申請が全て表示されることを確認するテスト
    public function test_approved_correction_requests_are_displayed(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        [,,, $finishedStatus] = $this->createAttendanceStatuses();
        [$pendingStatus, $approvedStatus] = $this->createRequestStatuses();

        $admin = $this->createAdminUser($adminRole);

        $user1 = $this->createStaffUser($userRole, '申請者A');
        $user2 = $this->createStaffUser($userRole, '申請者B');

        $attendance1 = $this->createAttendanceForUser($user1, $finishedStatus);
        $attendance2 = $this->createAttendanceForUser($user2, $finishedStatus);

        $this->createCorrectionRequest($attendance1, $user1, $approvedStatus, '承認済申請A');
        $this->createCorrectionRequest($attendance2, $user2, $approvedStatus, '承認済申請B');
        $this->createCorrectionRequest($attendance2, $user2, $pendingStatus, '承認待ち申請');

        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.list', [
            'status' => 'approved',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請者A');
        $response->assertSee('申請者B');
        $response->assertSee('承認済申請A');
        $response->assertSee('承認済申請B');
        $response->assertDontSee('承認待ち申請');
    }

    // 修正申請の詳細内容が正しく表示されることを確認するテスト
    public function test_correction_request_detail_is_displayed_correctly(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        [,,, $finishedStatus] = $this->createAttendanceStatuses();
        [$pendingStatus] = $this->createRequestStatuses();

        $admin = $this->createAdminUser($adminRole);
        $user = $this->createStaffUser($userRole, '申請者ユーザー');

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status_id' => $pendingStatus->id,
            'requested_clock_in_at' => '2026-04-10 09:30:00',
            'requested_clock_out_at' => '2026-04-10 18:30:00',
            'requested_note' => '電車遅延のため修正',
        ]);

        AttendanceCorrectionRequestBreak::create([
            'attendance_correction_request_id' => $request->id,
            'break_start_at' => '2026-04-10 12:30:00',
            'break_end_at' => '2026-04-10 13:30:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.show', [
            'attendance_correction_request_id' => $request->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請者ユーザー');
        $response->assertSee('2026年');
        $response->assertSee('4月10日');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:30');
        $response->assertSee('13:30');
        $response->assertSee('電車遅延のため修正');
    }

    // 修正申請の承認処理が正しく行われることを確認するテスト
    public function test_correction_request_is_approved_and_attendance_is_updated(): void
    {
        $adminRole = $this->createAdminRole();
        $userRole = $this->createUserRole();
        [,,, $finishedStatus] = $this->createAttendanceStatuses();
        [$pendingStatus, $approvedStatus] = $this->createRequestStatuses();

        $admin = $this->createAdminUser($adminRole);
        $user = $this->createStaffUser($userRole, '承認対象ユーザー');

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $request = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status_id' => $pendingStatus->id,
            'requested_clock_in_at' => '2026-04-10 09:30:00',
            'requested_clock_out_at' => '2026-04-10 18:30:00',
            'requested_note' => '管理者承認テスト',
        ]);

        AttendanceCorrectionRequestBreak::create([
            'attendance_correction_request_id' => $request->id,
            'break_start_at' => '2026-04-10 12:30:00',
            'break_end_at' => '2026-04-10 13:30:00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stamp_correction_request.approve', [
            'attendance_correction_request_id' => $request->id,
        ]));

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $request->id,
            'status_id' => $approvedStatus->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '2026-04-10 09:30:00',
            'clock_out_at' => '2026-04-10 18:30:00',
            'note' => '管理者承認テスト',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:30:00',
            'break_end_at' => '2026-04-10 13:30:00',
        ]);
    }
}
