<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 勤怠修正申請を扱うモデル
class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'attendance_id',
        'user_id',
        'status_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_note',
    ];

    // 型変換するカラム
    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
    ];

    // 関連する勤怠
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // 関連する申請者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 関連する申請状態
    public function status()
    {
        return $this->belongsTo(AttendanceCorrectionRequestStatus::class, 'status_id');
    }

    // 関連する申請休憩一覧
    public function breaks()
    {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class);
    }

    // 承認待ちか判定
    public function isPending(): bool
    {
        return optional($this->status)->code === 'pending';
    }

    // 承認済みか判定
    public function isApproved(): bool
    {
        return optional($this->status)->code === 'approved';
    }

    // 申請休憩合計秒数を取得
    public function breakSeconds(): int
    {
        return $this->breaks
            ->filter(fn($break) => $break->break_start_at && $break->break_end_at)
            ->sum(function ($break) {
                return $break->break_end_at->diffInSeconds($break->break_start_at);
            });
    }

    // 申請後の勤務合計秒数を取得
    public function workingSeconds(): int
    {
        if (!$this->requested_clock_in_at || !$this->requested_clock_out_at) {
            return 0;
        }

        $totalSeconds = $this->requested_clock_out_at->diffInSeconds($this->requested_clock_in_at);

        return max(0, $totalSeconds - $this->breakSeconds());
    }

    // 申請休憩合計時間を H:i 形式で取得
    public function formattedBreakTime(): string
    {
        $seconds = $this->breakSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // 申請後の勤務合計時間を H:i 形式で取得
    public function formattedWorkingTime(): string
    {
        $seconds = $this->workingSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
