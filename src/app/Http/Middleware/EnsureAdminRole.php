<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// 管理者以外のアクセスを制限するミドルウェア
class EnsureAdminRole
{
    // リクエストを処理
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 未ログインまたは管理者以外は管理者ログイン画面へ戻す
        if (!$user || !$user->isAdmin()) {
            return redirect()
                ->route('admin.login')
                ->with('error', '管理者としてログインしてください。');
        }

        return $next($request);
    }
}
