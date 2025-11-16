<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID2-1 メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        // 1. ユーザーを登録する
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->from(route('login'))
            ->post(route('login'), [
                'email'    => '',
                'password' => 'password123',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'email' => 'メールアドレスを入力してください',
                 ]);
    }

    /**
     * ID2-2 パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'email'    => 'user@example.com',
                'password' => '',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'password' => 'パスワードを入力してください',
                 ]);
    }

    /**
     * ID2-3 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'email'    => 'wrong@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'email' => 'ログイン情報が登録されていません',
                 ]);
    }
}