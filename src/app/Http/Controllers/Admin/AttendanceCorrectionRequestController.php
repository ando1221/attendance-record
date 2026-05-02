<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 管理者の修正申請一覧・承認画面・承認処理を扱うコントローラー
class AttendanceCorrectionRequestController extends Controller
{
    // 管理者用修正申請一覧画面を表示
    public function index(Request $request): View
    {
        // タブ状態。未指定なら承認待ち
        $status = $request->input('status', 'pending');

        // 指定状態の修正申請一覧を取得
        $requests = AttendanceCorrectionRequest::with(['user', 'attendance', 'status'])
            ->whereHas('status', function ($query) use ($status) {
                $query->where('code', $status);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('requests.list', [
            'title' => '申請一覧',
            'status' => $status,
            'requests' => $requests,
            'listRouteName' => 'admin.stamp_correction_request.list',
            'detailRouteName' => 'admin.stamp_correction_request.show',
            'routeParams' => [],
        ]);
    }

    // 修正申請承認画面を表示
    public function show(int $attendance_Correction_request_id): View
    {
        $requestData = AttendanceCorrectionRequest::with([
            'user',
            'attendance.breaks',
            'attendance.status',
            'breaks',
            'status',
        ])->findOrFail($attendance_Correction_request_id);

        return view('attendance.detail', [
            'requestData' => $requestData,
            'isAdminApprove' => true,
        ]);
    }
    
    // 修正申請を承認
    public function approve(int $attendance_Correction_request_id): RedirectResponse
    {
        // 対象の修正申請を取得
        $requestData = AttendanceCorrectionRequest::with([
            'attendance.breaks',
            'breaks',
            'status',
        ])->findOrFail($attendance_Correction_request_id);

        // すでに承認済みなら一覧へ戻す
        if (optional($requestData->status)->code === 'approved') {
            return redirect()
                ->route('admin.stamp_correction_request.list')
                ->with('error', 'この申請はすでに承認済みです。');
        }

        // 承認済みステータスを取得
        $approvedStatus = AttendanceCorrectionRequestStatus::where('code', 'approved')->firstOrFail();

        $requestData->getConnection()->transaction(function () use ($requestData, $approvedStatus) {
            // 対象勤怠を取得
            $attendance = $requestData->attendance;

            // 勤怠本体を申請内容で更新
            $attendance->update([
                'clock_in_at' => $requestData->requested_clock_in_at,
                'clock_out_at' => $requestData->requested_clock_out_at,
                'note' => $requestData->requested_note,
            ]);

            // 既存休憩を削除
            $attendance->breaks()->delete();

            // 申請休憩を勤怠休憩として再作成
            $requestData->breaks->each(function ($break) use ($attendance) {
                $attendance->breaks()->create([
                    'break_start_at' => $break->break_start_at,
                    'break_end_at' => $break->break_end_at,
                ]);
            });

            // 修正申請を承認済みに更新
            $requestData->status()->associate($approvedStatus);
            $requestData->save();
        });

        return redirect()
            ->route('admin.stamp_correction_request.list')
            ->with('success', '修正申請を承認しました。');
    }
}
