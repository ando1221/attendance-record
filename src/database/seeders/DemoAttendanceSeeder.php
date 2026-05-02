<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\AttendanceCorrectionRequestStatus;
use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAttendanceSeeder extends Seeder
{
  public function run(): void
  {
    // ===== マスタ取得 =====
    $adminRole = Role::where('code', 'admin')->firstOrFail();
    $userRole = Role::where('code', 'user')->firstOrFail();

    $finishedStatus = AttendanceStatus::where('code', 'finished')->firstOrFail();

    $pendingRequestStatus = AttendanceCorrectionRequestStatus::where('code', 'pending')->firstOrFail();
    $approvedRequestStatus = AttendanceCorrectionRequestStatus::where('code', 'approved')->firstOrFail();

    // ===== ユーザー作成（メール認証済み） =====
    $admin = User::updateOrCreate(
      ['email' => 'admin@example.com'],
      [
        'name' => '管理者ユーザー',
        'password' => Hash::make('password'),
        'role_id' => $adminRole->id,
        'email_verified_at' => now(),
      ]
    );

    $staff1 = User::updateOrCreate(
      ['email' => 'staff1@example.com'],
      [
        'name' => '山田太郎',
        'password' => Hash::make('password'),
        'role_id' => $userRole->id,
        'email_verified_at' => now(),
      ]
    );

    $staff2 = User::updateOrCreate(
      ['email' => 'staff2@example.com'],
      [
        'name' => '佐藤花子',
        'password' => Hash::make('password'),
        'role_id' => $userRole->id,
        'email_verified_at' => now(),
      ]
    );

    // ===== 対象ユーザーの既存データ削除 =====
    $targetUserIds = [$staff1->id, $staff2->id];

    $attendances = Attendance::whereIn('user_id', $targetUserIds)->get();

    foreach ($attendances as $attendance) {
      $requestIds = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)->pluck('id');

      AttendanceCorrectionRequestBreak::whereIn('attendance_correction_request_id', $requestIds)->delete();
      AttendanceCorrectionRequest::where('attendance_id', $attendance->id)->delete();
      AttendanceBreak::where('attendance_id', $attendance->id)->delete();
      $attendance->delete();
    }

    // ===== 固定勤怠データ定義（4月・5月・6月を各5日分） =====
    $attendanceDefinitions = [
      [
        'user' => $staff1,
        'work_date' => '2026-04-01',
        'clock_in_at' => '2026-04-01 09:00:00',
        'clock_out_at' => '2026-04-01 18:00:00',
        'note' => '4月通常勤務1',
        'breaks' => [
          ['start' => '2026-04-01 12:00:00', 'end' => '2026-04-01 13:00:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-04-02',
        'clock_in_at' => '2026-04-02 09:30:00',
        'clock_out_at' => '2026-04-02 18:15:00',
        'note' => '4月通常勤務2',
        'breaks' => [
          ['start' => '2026-04-02 12:15:00', 'end' => '2026-04-02 13:00:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-04-03',
        'clock_in_at' => '2026-04-03 08:45:00',
        'clock_out_at' => '2026-04-03 17:45:00',
        'note' => '4月早番勤務',
        'breaks' => [
          ['start' => '2026-04-03 12:00:00', 'end' => '2026-04-03 12:45:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-04-04',
        'clock_in_at' => '2026-04-04 10:00:00',
        'clock_out_at' => '2026-04-04 19:00:00',
        'note' => '4月午後外出あり',
        'breaks' => [
          ['start' => '2026-04-04 13:00:00', 'end' => '2026-04-04 14:00:00'],
          ['start' => '2026-04-04 16:30:00', 'end' => '2026-04-04 16:45:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-04-05',
        'clock_in_at' => '2026-04-05 09:10:00',
        'clock_out_at' => '2026-04-05 18:05:00',
        'note' => '4月通常勤務5',
        'breaks' => [
          ['start' => '2026-04-05 12:05:00', 'end' => '2026-04-05 13:00:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-05-01',
        'clock_in_at' => '2026-05-01 09:00:00',
        'clock_out_at' => '2026-05-01 18:00:00',
        'note' => '5月通常勤務1',
        'breaks' => [
          ['start' => '2026-05-01 12:00:00', 'end' => '2026-05-01 13:00:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-05-02',
        'clock_in_at' => '2026-05-02 09:20:00',
        'clock_out_at' => '2026-05-02 18:10:00',
        'note' => '5月通常勤務2',
        'breaks' => [
          ['start' => '2026-05-02 12:20:00', 'end' => '2026-05-02 13:05:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-05-03',
        'clock_in_at' => '2026-05-03 08:50:00',
        'clock_out_at' => '2026-05-03 17:40:00',
        'note' => '5月早番勤務',
        'breaks' => [
          ['start' => '2026-05-03 12:00:00', 'end' => '2026-05-03 12:50:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-05-04',
        'clock_in_at' => '2026-05-04 09:45:00',
        'clock_out_at' => '2026-05-04 18:20:00',
        'note' => '5月通常勤務4',
        'breaks' => [
          ['start' => '2026-05-04 12:30:00', 'end' => '2026-05-04 13:15:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-05-05',
        'clock_in_at' => '2026-05-05 10:00:00',
        'clock_out_at' => '2026-05-05 19:10:00',
        'note' => '5月遅番勤務',
        'breaks' => [
          ['start' => '2026-05-05 13:00:00', 'end' => '2026-05-05 14:00:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-06-01',
        'clock_in_at' => '2026-06-01 09:00:00',
        'clock_out_at' => '2026-06-01 18:00:00',
        'note' => '6月通常勤務1',
        'breaks' => [
          ['start' => '2026-06-01 12:00:00', 'end' => '2026-06-01 13:00:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-06-02',
        'clock_in_at' => '2026-06-02 09:25:00',
        'clock_out_at' => '2026-06-02 18:05:00',
        'note' => '6月通常勤務2',
        'breaks' => [
          ['start' => '2026-06-02 12:10:00', 'end' => '2026-06-02 13:00:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-06-03',
        'clock_in_at' => '2026-06-03 08:40:00',
        'clock_out_at' => '2026-06-03 17:30:00',
        'note' => '6月早番勤務',
        'breaks' => [
          ['start' => '2026-06-03 12:00:00', 'end' => '2026-06-03 12:45:00'],
        ],
      ],
      [
        'user' => $staff2,
        'work_date' => '2026-06-04',
        'clock_in_at' => '2026-06-04 10:10:00',
        'clock_out_at' => '2026-06-04 19:00:00',
        'note' => '6月遅番勤務',
        'breaks' => [
          ['start' => '2026-06-04 13:00:00', 'end' => '2026-06-04 14:00:00'],
          ['start' => '2026-06-04 16:20:00', 'end' => '2026-06-04 16:35:00'],
        ],
      ],
      [
        'user' => $staff1,
        'work_date' => '2026-06-05',
        'clock_in_at' => '2026-06-05 09:05:00',
        'clock_out_at' => '2026-06-05 18:10:00',
        'note' => '6月通常勤務5',
        'breaks' => [
          ['start' => '2026-06-05 12:05:00', 'end' => '2026-06-05 13:00:00'],
        ],
      ],
    ];

    // ===== 固定勤怠データ登録 =====
    $createdAttendances = [];

    foreach ($attendanceDefinitions as $definition) {
      $attendance = Attendance::create([
        'user_id' => $definition['user']->id,
        'status_id' => $finishedStatus->id,
        'work_date' => $definition['work_date'],
        'clock_in_at' => $definition['clock_in_at'],
        'clock_out_at' => $definition['clock_out_at'],
        'note' => $definition['note'],
      ]);

      foreach ($definition['breaks'] as $break) {
        AttendanceBreak::create([
          'attendance_id' => $attendance->id,
          'break_start_at' => $break['start'],
          'break_end_at' => $break['end'],
        ]);
      }

      $createdAttendances[$definition['work_date']] = $attendance;
    }

    // ===== 修正申請データ 1件目（承認待ち） =====
    $correctionRequest1 = AttendanceCorrectionRequest::create([
      'attendance_id' => $createdAttendances['2026-04-01']->id,
      'user_id' => $staff1->id,
      'status_id' => $pendingRequestStatus->id,
      'requested_clock_in_at' => '2026-04-01 08:55:00',
      'requested_clock_out_at' => '2026-04-01 18:00:00',
      'requested_note' => '電車遅延のため出勤時刻修正申請',
    ]);

    AttendanceCorrectionRequestBreak::create([
      'attendance_correction_request_id' => $correctionRequest1->id,
      'break_start_at' => '2026-04-01 12:00:00',
      'break_end_at' => '2026-04-01 13:00:00',
    ]);

    // ===== 修正申請データ 2件目（承認済み） =====
    $correctionRequest2 = AttendanceCorrectionRequest::create([
      'attendance_id' => $createdAttendances['2026-05-05']->id,
      'user_id' => $staff2->id,
      'status_id' => $approvedRequestStatus->id,
      'requested_clock_in_at' => '2026-05-05 10:00:00',
      'requested_clock_out_at' => '2026-05-05 19:20:00',
      'requested_note' => '退勤打刻漏れのため修正',
    ]);

    AttendanceCorrectionRequestBreak::create([
      'attendance_correction_request_id' => $correctionRequest2->id,
      'break_start_at' => '2026-05-05 13:00:00',
      'break_end_at' => '2026-05-05 14:00:00',
    ]);
  }
}
