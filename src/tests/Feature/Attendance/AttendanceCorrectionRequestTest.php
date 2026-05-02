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

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

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

    // テスト用勤怠データを作成する補助メソッド
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

    // 正常な修正申請パラメータを返す補助メソッド
    private function validRequestParams(): array
    {
        return [
            'requested_clock_in_at' => '09:00',
            'requested_clock_out_at' => '18:00',
            'requested_note' => '電車遅延のため修正申請',
            'breaks' => [
                [
                    'break_start_at' => '12:00',
                    'break_end_at' => '13:00',
                ],
            ],
        ];
    }

    // 出勤時間が退勤時間より後の場合、
    // 「出勤時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $params = $this->validRequestParams();
        $params['requested_clock_in_at'] = '19:00';
        $params['requested_clock_out_at'] = '18:00';

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->actingAs($user)
            ->post(route('attendance.correction_request.store', ['attendanceId' => $attendance->id]), $params);

        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'requested_clock_in_at' => '出勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後の場合、
    // 「休憩時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $params = $this->validRequestParams();
        $params['breaks'][0]['break_start_at'] = '18:30';
        $params['breaks'][0]['break_end_at'] = '19:00';

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->actingAs($user)
            ->post(route('attendance.correction_request.store', ['attendanceId' => $attendance->id]), $params);

        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後の場合、
    // 「休憩時間もしくは退勤時間が不適切な値です」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $params = $this->validRequestParams();
        $params['breaks'][0]['break_start_at'] = '17:30';
        $params['breaks'][0]['break_end_at'] = '18:30';

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->actingAs($user)
            ->post(route('attendance.correction_request.store', ['attendanceId' => $attendance->id]), $params);

        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考欄が未入力の場合、
    // 「備考を記入してください」が表示されることを確認するテスト
    public function test_validation_error_is_displayed_when_note_is_empty(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendanceForUser($user, $finishedStatus);

        $params = $this->validRequestParams();
        $params['requested_note'] = '';

        $response = $this->from(route('attendance.detail', ['id' => $attendance->id]))
            ->actingAs($user)
            ->post(route('attendance.correction_request.store', ['attendanceId' => $attendance->id]), $params);

        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors([
            'requested_note' => '備考を記入してください',
        ]);
    }
}
