<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // 会員登録後、認証メールが送信されることを確認するテスト
    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'verify@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // 未認証ユーザーがメール認証誘導画面を開けることを確認するテスト
    public function test_unverified_user_can_view_verification_notice_page(): void
    {
        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('メール認証');
    }

    // 誘導画面の認証ボタンから認証URLへ遷移できることを確認するテスト
    public function test_verification_notice_page_contains_verification_link(): void
    {
        Notification::fake();

        Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'verify-link@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'verify-link@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);

        // 認証導線画面に認証用の文言またはリンクが表示されていることを確認
        $response->assertSee('認証はこちらから');
    }

    // 認証リンクを開いたとき、メール認証が完了することを確認するテスト
    public function test_user_can_verify_email_from_signed_url(): void
    {
        Event::fake();

        $role = Role::create([
            'code' => 'user',
            'name' => '一般ユーザー',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        Event::assertDispatched(Verified::class);
    }
}
