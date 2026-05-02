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

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

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

    // 勤怠詳細画面の「名前」がログインユーザーの氏名になっていることを確認するテスト
    public function test_name_on_attendance_detail_is_logged_in_user_name(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:00:00'),
            'note' => '通常勤務',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
    }

    // 勤怠詳細画面の「日付」が選択した日付になっていることを確認するテスト
    public function test_date_on_attendance_detail_is_selected_date(): void
    {
        $role = $this->createUserRole();
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
            'note' => '通常勤務',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('4月10日');
    }

    // 出勤・退勤欄の時間がログインユーザーの打刻と一致していることを確認するテスト
    public function test_clock_in_and_clock_out_time_on_attendance_detail_match_user_record(): void
    {
        $role = $this->createUserRole();
        $finishedStatus = $this->createFinishedStatus();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::parse('2026-04-10 09:15:00'),
            'clock_out_at' => Carbon::parse('2026-04-10 18:30:00'),
            'note' => '通常勤務',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('09:15');
        $response->assertSee('18:30');
    }

    // 休憩欄の時間がログインユーザーの打刻と一致していることを確認するテスト
    public function test_break_time_on_attendance_detail_matches_user_record(): void
    {
        $role = $this->createUserRole();
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
            'note' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::parse('2026-04-10 12:00:00'),
            'break_end_at' => Carbon::parse('2026-04-10 13:00:00'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
