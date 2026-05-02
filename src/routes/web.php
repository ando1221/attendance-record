<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\Admin\Auth\AdminFortifyLoginController;
use App\Http\Controllers\FortifyLoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminAttendanceCorrectionRequestController;
use Illuminate\Support\Facades\Route;

// 会員登録送信は自作コントローラで受けて FormRequest でバリデーション
Route::middleware('guest')->post('/register', [RegisterController::class, 'store'])
    ->name('register');

// ログイン送信は自作コントローラで受けて FormRequest でバリデーション
Route::middleware('guest')->post('/login', [FortifyLoginController::class, 'store'])
    ->name('login');

// =========================
// 一般ユーザー側
// =========================
Route::middleware(['auth', 'verified'])->group(function () {
    // 出勤登録画面
    Route::get('/attendance', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 出勤処理
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock_in');

    // 退勤処理
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock_out');

    // 休憩入処理
    Route::post('/attendance/break/start', [AttendanceController::class, 'startBreak'])
        ->name('attendance.break.start');

    // 休憩戻処理
    Route::post('/attendance/break/end', [AttendanceController::class, 'endBreak'])
        ->name('attendance.break.end');

    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])
        ->name('attendance.list');

    // 勤怠詳細画面
    Route::get('/attendance/detail/{id}', [AttendanceListController::class, 'show'])
        ->name('attendance.detail');

    // 申請一覧画面（一般ユーザー）
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_request.list');

    // 修正申請詳細画面（一般ユーザー）
    Route::get('/stamp_correction_request/{attendance_correction_request_id}', [AttendanceCorrectionRequestController::class, 'show'])
        ->name('stamp_correction_request.show');

    // 修正申請送信
    Route::post('/attendance/correction-request/{attendanceId}', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('attendance.correction_request.store');
});

// =========================
// 管理者ログイン
// =========================
Route::get('/admin/login', [AdminFortifyLoginController::class, 'create'])
    ->name('admin.login');

Route::post('/admin/login', [AdminFortifyLoginController::class, 'store'])
    ->name('admin.login.store');

// =========================
// 管理者側
// =========================
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.list');

    Route::get('/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.list');

    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffIndex'])
        ->name('admin.attendance.staff');

    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])
        ->name('admin.attendance.staff.csv');

    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/stamp_correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
        ->name('admin.stamp_correction_request.list');

    Route::get('/stamp_correction_request/approve/{attendance_correction_request_id}', [AdminAttendanceCorrectionRequestController::class, 'show'])
        ->name('admin.stamp_correction_request.show');

    Route::post('/stamp_correction_request/approve/{attendance_correction_request_id}', [AdminAttendanceCorrectionRequestController::class, 'approve'])
        ->name('admin.stamp_correction_request.approve');
});
