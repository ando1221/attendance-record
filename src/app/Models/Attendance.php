<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 勤怠本体を扱うモデル
class Attendance extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'user_id',
        'status_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    // 型変換するカラム
    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    // 関連するユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 関連する勤怠状態
    public function status()
    {
        return $this->belongsTo(AttendanceStatus::class, 'status_id');
    }

    // 関連する休憩一覧
    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_id');
    }

    // 関連する修正申請一覧
    public function attendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'attendance_id');
    }

    // 現在の状態が勤務外か判定
    public function isOffDuty(): bool
    {
        return optional($this->status)->code === 'off_duty';
    }

    // 現在の状態が勤務中か判定
    public function isWorking(): bool
    {
        return optional($this->status)->code === 'working';
    }

    // 現在の状態が休憩中か判定
    public function isOnBreak(): bool
    {
        return optional($this->status)->code === 'on_break';
    }

    // 現在の状態が退勤済みか判定
    public function isFinished(): bool
    {
        return optional($this->status)->code === 'finished';
    }

    // 休憩中のレコードが存在するか判定
    public function hasOpenBreak(): bool
    {
        return $this->breaks()->whereNull('break_end_at')->exists();
    }

    // 出勤済みか判定
    public function hasClockedIn(): bool
    {
        return !is_null($this->clock_in_at);
    }

    // 退勤済みか判定
    public function hasClockedOut(): bool
    {
        return !is_null($this->clock_out_at);
    }

    // 休憩合計秒数を取得
    public function breakSeconds(): int
    {
        return $this->breaks
            ->filter(fn($break) => $break->break_start_at && $break->break_end_at)
            ->sum(function ($break) {
                return $break->break_end_at->diffInSeconds($break->break_start_at);
            });
    }

    // 勤務合計秒数を取得
    public function workingSeconds(): int
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return 0;
        }

        $totalSeconds = $this->clock_out_at->diffInSeconds($this->clock_in_at);

        return max(0, $totalSeconds - $this->breakSeconds());
    }

    // 休憩合計時間を H:i 形式で取得
    public function formattedBreakTime(): string
    {
        $seconds = $this->breakSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // 勤務合計時間を H:i 形式で取得
    public function formattedWorkingTime(): string
    {
        $seconds = $this->workingSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
