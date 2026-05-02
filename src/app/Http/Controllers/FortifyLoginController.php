<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;

// ログイン送信を受けるコントローラー
class FortifyLoginController extends Controller
{
    // ログイン処理
    public function store(LoginRequest $request): RedirectResponse|LoginResponse
    {
        // 入力値を取得
        $credentials = $request->only('email', 'password');

        // 認証失敗時
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'ログイン情報が登録されていません',
                ])
                ->withInput($request->only('email'));
        }

        // セッション再生成
        $request->session()->regenerate();

        // Fortify標準のログイン後レスポンスを返す
        return app(LoginResponse::class);
    }
}
