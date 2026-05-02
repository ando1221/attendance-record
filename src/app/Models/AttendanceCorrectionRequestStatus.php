<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 修正申請状態マスタを扱うモデル
class AttendanceCorrectionRequestStatus extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'code',
    ];

    // 関連する修正申請一覧
    public function attendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'status_id');
    }

    // 承認待ちか判定
    public function isPending(): bool
    {
        return $this->code === 'pending';
    }

    // 承認済みか判定
    public function isApproved(): bool
    {
        return $this->code === 'approved';
    }
}
