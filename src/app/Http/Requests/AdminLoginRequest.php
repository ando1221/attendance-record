<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

// 管理者ログイン画面の入力バリデーションを扱うリクエスト
class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',

            'password.required' => 'パスワードを入力してください',
        ];
    }

    // 追加バリデーション
    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            // 既に入力バリデーションエラーがある場合は認証チェックしない
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // 認証情報を取得
            $credentials = $this->only('email', 'password');

            // メールアドレス・パスワードの組み合わせを確認
            if (!Auth::validate($credentials)) {
                $validator->errors()->add(
                    'email',
                    'ログイン情報が登録されていません'
                );
                return;
            }

            // ユーザーを取得
            $user = User::where('email', $this->input('email'))->first();

            // 管理者でない場合も同じメッセージを返す
            if (!$user || !$user->isAdmin()) {
                $validator->errors()->add(
                    'email',
                    'ログイン情報が登録されていません'
                );
            }
        });
    }
}
