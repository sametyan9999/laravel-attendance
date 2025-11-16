<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信される
     *
     * 1. 会員登録をする
     * 2. 認証メールを送信する
     * → 登録したメールアドレス宛に認証メールが送信されている
     *
     * @test
     */
    public function 会員登録後_認証メールが送信される()
    {
        Notification::fake();

        // 会員登録
        $response = $this->post('/register', [
            'name'                  => 'テストユーザー',
            'email'                 => 'test@example.com',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(302);

        // ユーザーが作成されていること
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 登録直後は未認証であること
        $this->assertNull($user->email_verified_at);

        // 認証メール（VerifyEmail 通知）が送信されていること
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下すると
     * メール認証サイトに遷移する（誘導 UI が正しく表示されている）
     *
     * 1. メール認証導線画面を表示する
     * 2. 「認証はこちらから」ボタンを押下
     * 3. メール認証サイトを表示する
     * → メール認証サイトに遷移する
     *
     * ※ HTTP テストでは外部サイト遷移そのものは確認できないため、
     *    誘導画面にボタン文言が表示されていることを確認する。
     *
     * @test
     */
    public function メール認証誘導画面に認証はこちらからボタンが表示されている()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // メール認証誘導画面 (/email/verify) を表示
        $response = $this->actingAs($user)
            ->get(route('verification.notice'));

        $response->assertStatus(200);

        // 画面上に「認証はこちらから」というボタン／リンクの文言が表示されていること
        $response->assertSee('認証はこちらから');
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     *
     * 1. メール認証を完了する
     * 2. 勤怠登録画面を表示する
     * → 勤怠登録画面に遷移する
     *
     * @test
     */
    public function メール認証完了後_勤怠登録画面に遷移する()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 認証リンク URL を生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // ユーザーとしてログインした状態で認証リンクにアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // 勤怠登録画面（/attendance）へリダイレクトされる
        $response->assertRedirect(route('attendance.today'));

        // ユーザーが認証済みになっていること
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}