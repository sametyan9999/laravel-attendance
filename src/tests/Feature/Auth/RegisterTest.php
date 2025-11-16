<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID1-1 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_名前が未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => '',
                'email'                 => 'user@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /**
     * ID1-2 メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => 'テスト太郎',
                'email'                 => '',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    /**
     * ID1-3 パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが8文字未満の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => 'テスト太郎',
                'email'                 => 'user@example.com',
                'password'              => 'abcdefg', // 7文字
                'password_confirmation' => 'abcdefg',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
    }

    /**
     * ID1-4 パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが一致しない場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => 'テスト太郎',
                'email'                 => 'user@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password999',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
    }

    /**
     * ID1-5 パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => 'テスト太郎',
                'email'                 => 'user@example.com',
                'password'              => '',
                'password_confirmation' => '',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
    }

    /**
     * ID1-6 フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_フォームに内容が入力されていた場合_データが正常に保存される()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name'                  => 'テスト太郎',
                'email'                 => 'user@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name'  => 'テスト太郎',
            'email' => 'user@example.com',
        ]);
    }
}