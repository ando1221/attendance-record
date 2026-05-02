<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

// 管理者のみアクセス可能にするミドルウェア
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 未ログインは拒否
        if (!auth()->check()) {
            abort(403);
        }

        // ログインユーザーを取得
        /** @var User $user */
        $user = auth()->user();

        // 管理者以外は拒否
        if (!$user->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
