<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// 管理者ログインを扱うコントローラー
class AdminFortifyLoginController extends Controller
{
    // 管理者ログイン画面を表示
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // 管理者でログイン済みなら管理者一覧へ戻す
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.attendance.list');
        }

        // 一般ユーザーでログイン済みなら一般ユーザー画面へ戻す
        if ($user && !$user->isAdmin()) {
            return redirect()->route('attendance.show');
        }

        // 未ログイン時のみ管理者ログイン画面を表示
        return view('admin.auth.login');
    }

    // 管理者ログイン処理
    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (!$request->user()->isAdmin()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->onlyInput('email');
        }

        return redirect()->route('admin.attendance.list');
    }
}
