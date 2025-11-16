<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID3-1 メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('admin.login.form'))
            ->post(route('admin.login'), [
                'email'    => '',
                'password' => 'Admin1234',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'email' => 'メールアドレスを入力してください',
                 ]);
    }

    /**
     * ID3-2 パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('admin.login.form'))
            ->post(route('admin.login'), [
                'email'    => 'admin@example.com',
                'password' => '',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'password' => 'パスワードを入力してください',
                 ]);
    }

    /**
     * ID3-3 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('admin.login.form'))
            ->post(route('admin.login'), [
                'email'    => 'wrong-admin@example.com',
                'password' => 'Admin1234',
            ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors([
                     'email' => 'ログイン情報が登録されていません',
                 ]);
    }
}