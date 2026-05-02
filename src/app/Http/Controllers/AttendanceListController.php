<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 一般ユーザーの勤怠一覧・詳細を扱うコントローラー
class AttendanceListController extends Controller
{
    // 勤怠一覧画面を表示
    public function index(Request $request): View
    {
        $month = $request->input('month', now()->format('Y-m'));

        // 対象月の勤務外勤怠を不足分補完
        $this->ensureMonthlyAttendances($request->user()->id, $month);

        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $attendances = Attendance::with(['breaks', 'status'])
            ->where('user_id', $request->user()->id)
            ->whereBetween('work_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $days = collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->map(function ($date) use ($attendances) {
                $dateString = $date->format('Y-m-d');

                return [
                    'date' => $date->copy(),
                    'attendance' => $attendances->get($dateString),
                ];
            });

        return view('attendance.list', [
            'title' => '勤怠一覧',
            'month' => $month,
            'days' => $days,
            'listRouteName' => 'attendance.list',
            'detailRouteName' => 'attendance.detail',
            'monthSelectRouteName' => 'attendance.list',
            'routeParams' => [],
        ]);
    }

    // 勤怠詳細画面を表示
    public function show(Request $request, int $id): View
    {
        $attendance = Attendance::with([
            'user',
            'status',
            'breaks',
            'attendanceCorrectionRequests.user',
            'attendanceCorrectionRequests.status',
            'attendanceCorrectionRequests.breaks',
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return view('attendance.detail', [
            'attendance' => $attendance,
        ]);
    }

    // 対象月の不足勤怠を勤務外で補完
    private function ensureMonthlyAttendances(int $userId, string $month): void
    {
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $offDutyStatus = AttendanceStatus::where('code', 'off_duty')->firstOrFail();

        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
            Attendance::firstOrCreate(
                [
                    'user_id' => $userId,
                    'work_date' => $date->format('Y-m-d'),
                ],
                [
                    'status_id' => $offDutyStatus->id,
                    'clock_in_at' => null,
                    'clock_out_at' => null,
                    'note' => null,
                ]
            );
        }
    }
}
