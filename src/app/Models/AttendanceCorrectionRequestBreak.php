<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 修正申請に紐づく休憩を扱うモデル
class AttendanceCorrectionRequestBreak extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'attendance_correction_request_id',
        'break_start_at',
        'break_end_at',
    ];

    // 型変換するカラム
    protected $casts = [
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
    ];

    // 関連する修正申請
    public function attendanceCorrectionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class);
    }

    // 申請休憩終了済みか判定
    public function isFinished(): bool
    {
        return !is_null($this->break_end_at);
    }

    // 申請休憩中か判定
    public function isInProgress(): bool
    {
        return !is_null($this->break_start_at) && is_null($this->break_end_at);
    }

    // 申請休憩時間を秒で取得
    public function breakSeconds(): int
    {
        if (!$this->break_start_at || !$this->break_end_at) {
            return 0;
        }

        return $this->break_end_at->diffInSeconds($this->break_start_at);
    }

    // 申請休憩時間を H:i 形式で取得
    public function formattedBreakTime(): string
    {
        $seconds = $this->breakSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
