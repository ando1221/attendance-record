<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 一般ユーザーの打刻画面表示と打刻処理を扱うコントローラー
class AttendanceController extends Controller
{
    // 出勤登録画面を表示
    public function show(Request $request): View
    {
        $user = $request->user();

        $attendance = Attendance::with(['status', 'breaks'])
            ->where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        return view('attendance.index', compact('attendance'));
    }

    // 出勤処理
    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        $workingStatus = AttendanceStatus::where('code', 'working')->firstOrFail();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->firstOrFail();

        $attendance->update([
            'status_id' => $workingStatus->id,
            'clock_in_at' => now(),
        ]);

        return redirect()->route('attendance.show');
    }

    // 退勤処理
    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user();

        $finishedStatus = AttendanceStatus::where('code', 'finished')->firstOrFail();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->firstOrFail();

        $attendance->update([
            'status_id' => $finishedStatus->id,
            'clock_out_at' => now(),
        ]);

        return redirect()->route('attendance.show');
    }

    // 休憩入処理
    public function startBreak(Request $request): RedirectResponse
    {
        $user = $request->user();

        $onBreakStatus = AttendanceStatus::where('code', 'on_break')->firstOrFail();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->firstOrFail();

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        $attendance->update([
            'status_id' => $onBreakStatus->id,
        ]);

        return redirect()->route('attendance.show');
    }

    // 休憩戻処理
    public function endBreak(Request $request): RedirectResponse
    {
        $user = $request->user();

        $workingStatus = AttendanceStatus::where('code', 'working')->firstOrFail();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->firstOrFail();

        $attendanceBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->latest('id')
            ->firstOrFail();

        $attendanceBreak->update([
            'break_end_at' => now(),
        ]);

        $attendance->update([
            'status_id' => $workingStatus->id,
        ]);

        return redirect()->route('attendance.show');
    }
}
