<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 勤怠状態マスタを扱うモデル
class AttendanceStatus extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'code',
    ];

    // 関連する勤怠一覧
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'status_id');
    }

    // 勤務外状態か判定
    public function isOffDuty(): bool
    {
        return $this->code === 'off_duty';
    }

    // 勤務中状態か判定
    public function isWorking(): bool
    {
        return $this->code === 'working';
    }

    // 休憩中状態か判定
    public function isOnBreak(): bool
    {
        return $this->code === 'on_break';
    }

    // 退勤済み状態か判定
    public function isFinished(): bool
    {
        return $this->code === 'finished';
    }
}
