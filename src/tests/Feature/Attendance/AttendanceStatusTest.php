<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    // 勤務外の場合、勤怠ステータスが正しく表示されることを確認するテスト
    public function test_off_duty_status_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 9, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $offDutyStatus = AttendanceStatus::create([
            'code' => 'off_duty',
            'name' => '勤務外',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $offDutyStatus->id,
            'work_date' => today(),
            'clock_in_at' => null,
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    // 出勤中の場合、勤怠ステータスが正しく表示されることを確認するテスト
    public function test_working_status_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 9, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $workingStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    // 休憩中の場合、勤怠ステータスが正しく表示されることを確認するテスト
    public function test_on_break_status_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $onBreakStatus = AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $onBreakStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    // 退勤済の場合、勤怠ステータスが正しく表示されることを確認するテスト
    public function test_finished_status_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 18, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $finishedStatus = AttendanceStatus::create([
            'code' => 'finished',
            'name' => '退勤済',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => now()->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}
