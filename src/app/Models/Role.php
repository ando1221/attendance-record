<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 権限マスタを扱うモデル
class Role extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'code',
    ];

    // 関連するユーザー一覧
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // 一般ユーザー権限か判定
    public function isUser(): bool
    {
        return $this->code === 'user';
    }

    // 管理者権限か判定
    public function isAdmin(): bool
    {
        return $this->code === 'admin';
    }
}
