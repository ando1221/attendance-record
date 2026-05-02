<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    // 休憩入ボタンが表示され、休憩処理後にステータスが休憩中になることを確認するテスト
    public function test_break_start_button_works_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
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
            'status_id' => $workingStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩入');
        $response->assertSee('<button type="submit" class="action-button action-button--sub"', false);

        $response = $this->actingAs($user)->post(route('attendance.break.start'));

        $response->assertRedirect(route('attendance.show'));

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->firstOrFail();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'status_id' => $onBreakStatus->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    // 休憩は一日に何回でもできることを確認するテスト
    public function test_break_can_be_taken_multiple_times_in_one_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
        ]);

        AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
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

        $this->actingAs($user)->post(route('attendance.break.start'));

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 30, 0));
        $this->actingAs($user)->post(route('attendance.break.end'));

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩入');

        Carbon::setTestNow();
    }

    // 休憩戻ボタンが正しく機能し、処理後に出勤中へ戻ることを確認するテスト
    public function test_break_end_button_works_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
        ]);

        $onBreakStatus = AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $onBreakStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $attendance->breaks()->create([
            'break_start_at' => now()->copy()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩戻');

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 30, 0));

        $response = $this->actingAs($user)->post(route('attendance.break.end'));

        $response->assertRedirect(route('attendance.show'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status_id' => $workingStatus->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_end_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show'));
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    // 休憩戻は一日に何回でもできることを確認するテスト
    public function test_break_end_can_be_done_multiple_times_in_one_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $workingStatus = AttendanceStatus::create([
            'code' => 'working',
            'name' => '出勤中',
        ]);

        AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
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

        $this->actingAs($user)->post(route('attendance.break.start'));

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 30, 0));
        $this->actingAs($user)->post(route('attendance.break.end'));

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 15, 0, 0));
        $this->actingAs($user)->post(route('attendance.break.start'));

        $response = $this->actingAs($user)->get(route('attendance.show'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩戻');

        Carbon::setTestNow();
    }

    // 休憩時刻が正確に記録されることを確認するテスト
    public function test_break_time_is_recorded_correctly(): void
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

        AttendanceStatus::create([
            'code' => 'on_break',
            'name' => '休憩中',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status_id' => $workingStatus->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 0, 0));
        $this->actingAs($user)->post(route('attendance.break.start'));

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 12, 30, 0));
        $this->actingAs($user)->post(route('attendance.break.end'));

        $attendance->refresh();
        $attendance->load('breaks');

        $this->assertCount(1, $attendance->breaks);

        $break = $attendance->breaks->first();

        $this->assertNotNull($break->break_start_at);
        $this->assertNotNull($break->break_end_at);
        $this->assertSame(30, $break->break_start_at->diffInMinutes($break->break_end_at));

        Carbon::setTestNow();
    }
}
