<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    // 退勤ボタンが表示され、退勤処理後にステータスが退勤済になることを確認するテスト
    public function test_clock_out_button_works_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 18, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
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
            'status_id' => $workingStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSeeText('退勤');
        $response->assertSee('<button type="submit" class="action-button"', false);

        $response = $this->actingAs($user)->post(route('attendance.clock_out'));

        $response->assertRedirect(route('attendance.show'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    // 退勤時刻が勤怠一覧画面で確認できることを確認するテスト
    public function test_clock_out_time_is_displayed_on_attendance_list(): void
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

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
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
            'status_id' => $offDutyStatus->id,
            'work_date' => today(),
            'clock_in_at' => null,
            'clock_out_at' => null,
            'note' => null,
        ]);

        $this->actingAs($user)->post(route('attendance.clock_in'));

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 18, 0, 0));
        $this->actingAs($user)->post(route('attendance.clock_out'));

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => now()->format('Y-m'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('18:00');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status_id' => $finishedStatus->id,
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        Carbon::setTestNow();
    }
}
