<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceEditRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\AttendanceCorrectionRequestStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 一般ユーザーの修正申請一覧・詳細・送信を扱うコントローラー
class AttendanceCorrectionRequestController extends Controller
{
    // 修正申請一覧画面を表示
    public function index(Request $request): View
    {
        // 現在表示する申請状態を取得
        // 未指定の場合は承認待ちタブを初期表示にする
        $status = $request->input('status', 'pending');

        // ログインユーザー本人の申請だけを取得
        // 勤怠・状態・休憩・申請者情報も一緒に読み込む
        $requests = AttendanceCorrectionRequest::with(['attendance', 'status', 'breaks', 'user'])
            ->where('user_id', $request->user()->id)
            ->whereHas('status', function ($query) use ($status) {
                $query->where('code', $status);
            })
            ->orderByDesc('created_at')
            ->get();

        // 共通の申請一覧ビューへ渡す
        return view('requests.list', [
            'title' => '申請一覧',
            'status' => $status,
            'requests' => $requests,
            'listRouteName' => 'stamp_correction_request.list',
            'detailRouteName' => 'stamp_correction_request.show',
            'routeParams' => [],
        ]);
    }

    // 修正申請詳細画面を表示
    public function show(Request $request, int $attendance_correction_request_id): View
    {
        // ログインユーザー本人の申請のみ表示可能にする
        // 勤怠本体・休憩・状態も合わせて読み込む
        $requestData = AttendanceCorrectionRequest::with([
            'user',
            'attendance.breaks',
            'attendance.status',
            'breaks',
            'status',
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($attendance_correction_request_id);

        // 勤怠詳細ビューを申請詳細表示モードで使用する
        return view('attendance.detail', [
            'requestData' => $requestData,
            'isAdminApprove' => false,
            'isRequestDetail' => true,
        ]);
    }

    // 修正申請を保存
    public function store(AttendanceEditRequest $request, int $attendanceId): RedirectResponse
    {
        // 対象勤怠を取得
        // 休憩と過去の修正申請状態も同時に読み込む
        $attendance = Attendance::with(['breaks', 'attendanceCorrectionRequests.status'])->find($attendanceId);

        // 対象勤怠が存在しない場合は一覧へ戻す
        if (!$attendance) {
            return redirect()
                ->route('attendance.list')
                ->with('error', '対象の勤怠データが存在しません。');
        }

        // 自分以外の勤怠に対する修正申請は不可
        if ($attendance->user_id !== $request->user()->id) {
            return redirect()
                ->route('attendance.list')
                ->with('error', '自分の勤怠から修正申請を行ってください。');
        }

        // 勤務実績がある日かどうかを判定
        // 勤務外補完レコードのように、出退勤も休憩もない日は申請不可にする
        $hasActualAttendance =
            !is_null($attendance->clock_in_at) ||
            !is_null($attendance->clock_out_at) ||
            $attendance->breaks->isNotEmpty();

        if (!$hasActualAttendance) {
            return redirect()
                ->route('attendance.detail', ['id' => $attendance->id])
                ->with('error', '勤務外の日は修正申請できません。');
        }

        // すでに承認待ちの申請がある場合は新規申請を不可にする
        $hasPendingRequest = $attendance->attendanceCorrectionRequests->contains(function ($correctionRequest) {
            return optional($correctionRequest->status)->code === 'pending';
        });

        if ($hasPendingRequest) {
            return redirect()
                ->route('attendance.detail', ['id' => $attendance->id])
                ->with('error', '承認待ちのため修正はできません。');
        }

        // 勤務日の年月日部分を取得
        // 入力された HH:MM と結合して datetime を作るために使用する
        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        // 入力された出勤・退勤時刻を datetime に変換
        $requestedClockInAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $workDate . ' ' . $request->requested_clock_in_at . ':00'
        );

        $requestedClockOutAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $workDate . ' ' . $request->requested_clock_out_at . ':00'
        );

        // 備考は前後の空白を除去して比較・保存に使う
        $requestedNote = trim($request->requested_note);

        // 入力された休憩を整形
        // 開始・終了の両方が入っている休憩だけを申請対象にする
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

        // 元の勤怠に紐づく休憩を比較しやすい形式へ整形
        $originalBreaks = $attendance->breaks
            ->map(function ($break) {
                return [
                    'break_start_at' => $break->break_start_at ? $break->break_start_at->format('H:i:s') : null,
                    'break_end_at' => $break->break_end_at ? $break->break_end_at->format('H:i:s') : null,
                ];
            })
            ->values()
            ->toArray();

        // 入力休憩も比較用に H:i:s 配列へ変換
        $normalizedRequestedBreaks = collect($requestedBreaks)
            ->map(function ($break) {
                return [
                    'break_start_at' => $break['break_start_at']->format('H:i:s'),
                    'break_end_at' => $break['break_end_at']->format('H:i:s'),
                ];
            })
            ->toArray();

        // 元データと比較し、変更箇所があるかを判定
        // 出勤・退勤・備考・休憩のいずれかが変わっていれば true
        $hasChanged =
            optional($attendance->clock_in_at)->format('H:i:s') !== $requestedClockInAt->format('H:i:s') ||
            optional($attendance->clock_out_at)->format('H:i:s') !== $requestedClockOutAt->format('H:i:s') ||
            trim((string) $attendance->note) !== $requestedNote ||
            $originalBreaks !== $normalizedRequestedBreaks;

        // 差分がなければ申請を作成せずに戻す
        if (!$hasChanged) {
            return redirect()
                ->route('attendance.detail', ['id' => $attendance->id])
                ->with('error', '変更箇所がないため修正申請は送信されませんでした。');
        }

        // 承認待ちステータスを取得
        $pendingStatus = AttendanceCorrectionRequestStatus::where('code', 'pending')->firstOrFail();

        // 修正申請本体を作成
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $request->user()->id,
            'status_id' => $pendingStatus->id,
            'requested_clock_in_at' => $requestedClockInAt,
            'requested_clock_out_at' => $requestedClockOutAt,
            'requested_note' => $requestedNote,
        ]);

        // 修正申請に紐づく休憩を作成
        foreach ($requestedBreaks as $break) {
            AttendanceCorrectionRequestBreak::create([
                'attendance_correction_request_id' => $correctionRequest->id,
                'break_start_at' => $break['break_start_at'],
                'break_end_at' => $break['break_end_at'],
            ]);
        }

        // 申請一覧へ戻し、送信完了メッセージを表示
        return redirect()
            ->route('stamp_correction_request.list')
            ->with('success', '修正申請を送信しました。');
    }
}
