<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** 未ログインでトップページにアクセスすると 勤怠画面へリダイレクトされる */
    public function test_未ログインでトップページにアクセスすると_勤怠画面へリダイレクトされる()
    {
        $response = $this->get('/');

        $response->assertStatus(302)
                 ->assertRedirect('/attendance'); // 実際のリダイレクト先
    }
}