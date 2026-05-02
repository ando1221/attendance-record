<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceEditRequest;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

// 管理者の勤怠一覧・詳細・スタッフ別勤怠一覧を扱うコントローラー
class AttendanceController extends Controller
{
    // 管理者用勤怠一覧画面を表示
    public function index(Request $request): View
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $attendances = Attendance::with(['user', 'status', 'breaks'])
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.list', [
            'title' => Carbon::parse($date)->format('Y年n月j日') . 'の勤怠',
            'date' => $date,
            'attendances' => $attendances,
            'listRouteName' => 'admin.attendance.list',
            'detailRouteName' => 'admin.attendance.show',
            'dateSelectRouteName' => 'admin.attendance.list',
            'routeParams' => [],
        ]);
    }

    // 管理者用勤怠詳細画面を表示
    public function show(int $id): View
    {
        $attendance = Attendance::with([
            'user',
            'status',
            'breaks',
            'attendanceCorrectionRequests.user',
            'attendanceCorrectionRequests.status',
            'attendanceCorrectionRequests.breaks',
        ])->findOrFail($id);

        return view('attendance.detail', [
            'attendance' => $attendance,
            'isAdminEdit' => true,
        ]);
    }

    // 管理者が勤怠を即時更新
    public function update(AttendanceEditRequest $request, int $id): RedirectResponse
    {
        /** @var User|null $authUser */
        $authUser = auth()->user();

        if (!$authUser || !$authUser->isAdmin()) {
            return redirect()
                ->route('admin.login')
                ->with('error', '管理者としてログインしてください。');
        }

        $attendance = Attendance::with('breaks')->find($id);

        if (!$attendance) {
            return redirect()
                ->route('admin.attendance.list')
                ->with('error', '対象の勤怠データが存在しません。');
        }

        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $clockInAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $workDate . ' ' . $request->requested_clock_in_at . ':00'
        );

        $clockOutAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $workDate . ' ' . $request->requested_clock_out_at . ':00'
        );

        $requestedNote = trim($request->requested_note);

        // 入力された休憩を整形
        $requestedBreaks = collect($request->input('breaks', []))
            ->filter(function ($break) {
                return !empty($break['break_start_at']) && !empty($break['break_end_at']);
            })
            ->map(function ($break) use ($workDate) {
                return [
                    'break_start_at' => Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $workDate . ' ' . $break['break_start_at'] . ':00'
                    ),
                    'break_end_at' => Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $workDate . ' ' . $break['break_end_at'] . ':00'
                    ),
                ];
            })
            ->values()
            ->toArray();

        // 元の休憩を整形
        $originalBreaks = $attendance->breaks
            ->map(function ($break) {
                return [
                    'break_start_at' => $break->break_start_at ? $break->break_start_at->format('H:i:s') : null,
                    'break_end_at' => $break->break_end_at ? $break->break_end_at->format('H:i:s') : null,
                ];
            })
            ->values()
            ->toArray();

        $normalizedRequestedBreaks = collect($requestedBreaks)
            ->map(function ($break) {
                return [
                    'break_start_at' => $break['break_start_at']->format('H:i:s'),
                    'break_end_at' => $break['break_end_at']->format('H:i:s'),
                ];
            })
            ->toArray();

        // 差分なし判定
        $hasChanged =
            optional($attendance->clock_in_at)->format('H:i:s') !== $clockInAt->format('H:i:s') ||
            optional($attendance->clock_out_at)->format('H:i:s') !== $clockOutAt->format('H:i:s') ||
            trim((string) $attendance->note) !== $requestedNote ||
            $originalBreaks !== $normalizedRequestedBreaks;

        if (!$hasChanged) {
            return redirect()
                ->route('admin.attendance.show', ['id' => $attendance->id])
                ->with('error', '変更箇所がないため修正申請は送信されませんでした。');
        }

        $attendance->update([
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'note' => $requestedNote,
        ]);

        $attendance->breaks()->delete();

        foreach ($requestedBreaks as $break) {
            $attendance->breaks()->create([
                'break_start_at' => $break['break_start_at'],
                'break_end_at' => $break['break_end_at'],
            ]);
        }

        return redirect()
            ->route('admin.attendance.show', [
                'id' => $attendance->id,
            ])
            ->with('success', '勤怠を修正しました。');
    }

    // スタッフ別勤怠一覧画面を表示
    public function staffIndex(Request $request, int $id): View
    {
        $user = User::findOrFail($id);

        $month = $request->input('month', now()->format('Y-m'));

        $this->ensureMonthlyAttendances($user->id, $month);

        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $attendances = Attendance::with(['breaks', 'status'])
            ->where('user_id', $user->id)
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
            'title' => $user->name . 'さんの勤怠',
            'month' => $month,
            'days' => $days,
            'listRouteName' => 'admin.attendance.staff',
            'detailRouteName' => 'admin.attendance.show',
            'monthSelectRouteName' => 'admin.attendance.staff',
            'routeParams' => ['id' => $user->id],
            'isAdminCsvExport' => true,
            'csvRouteName' => 'admin.attendance.staff.csv',
        ]);
    }

    // スタッフ別月次勤怠をCSV出力
    public function exportCsv(Request $request, int $id): StreamedResponse
    {
        $user = User::findOrFail($id);

        $month = $request->input('month', now()->format('Y-m'));

        $this->ensureMonthlyAttendances($user->id, $month);

        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $attendances = Attendance::with(['breaks'])
            ->where('user_id', $user->id)
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

        $fileName = $user->name . '_' . $month . '_attendance.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($days) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($days as $day) {
                $date = $day['date'];
                $attendance = $day['attendance'];

                $totalBreakMinutes = 0;
                $workMinutes = 0;

                if ($attendance) {
                    $breaks = collect($attendance->breaks ?? []);

                    $totalBreakMinutes = $breaks->sum(function ($break) {
                        if (!$break->break_start_at || !$break->break_end_at) {
                            return 0;
                        }

                        return $break->break_start_at->diffInMinutes($break->break_end_at);
                    });

                    if ($attendance->clock_in_at && $attendance->clock_out_at) {
                        $workMinutes = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) - $totalBreakMinutes;
                    }
                }

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;
                $workHours = floor(max($workMinutes, 0) / 60);
                $workRemainMinutes = max($workMinutes, 0) % 60;

                $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
                $weekDay = $weekDays[$date->dayOfWeek];

                fputcsv($handle, [
                    $date->format('Y/m/d') . '（' . $weekDay . '）',
                    $attendance && $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '',
                    $attendance && $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '',
                    $attendance ? sprintf('%02d:%02d', $breakHours, $breakMinutes) : '',
                    $attendance ? sprintf('%02d:%02d', $workHours, $workRemainMinutes) : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
