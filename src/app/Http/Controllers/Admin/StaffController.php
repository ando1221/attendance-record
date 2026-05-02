<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

// 管理者のスタッフ一覧画面を扱うコントローラー
class StaffController extends Controller
{
    // スタッフ一覧画面を表示
    public function index(): View
    {
        // 一般ユーザーのみ取得
        $staffs = User::with('role')
            ->whereHas('role', function ($query) {
                $query->where('code', 'user');
            })
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', [
            'title' => 'スタッフ一覧',
            'staffs' => $staffs,
        ]);
    }
}
