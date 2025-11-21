<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $att = Attendance::factory()->create([
            'user_id'      => $user->id,
            'work_date'    => '2024-01-10',
            'clock_in_at'  => '2024-01-10 09:00:00',
            'clock_out_at' => '2024-01-10 18:00:00',
        ]);

        $this->actingAs($admin)
            ->get("/admin/attendance/{$att->id}")
            ->assertStatus(200)
            ->assertSee('2024-01-10')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $att = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2024-01-10',
        ]);

        $this->actingAs($admin)
            ->put("/admin/attendance/{$att->id}", [
                'clock_in'  => '18:00',
                'clock_out' => '09:00',
            ])
            ->assertSessionHasErrors(['clock_out'])
            // ★ テストケース一覧に合わせてメッセージ修正
            ->assertSessionHasErrors([
                'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $att = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2024-01-10',
        ]);

        $this->actingAs($admin)
            ->put("/admin/attendance/{$att->id}", [
                'clock_in'  => '09:00',
                'clock_out' => '10:00',
                'break1_in' => '11:00',
            ])
            // ★ 「休憩時間が不適切な値です」に変更
            ->assertSessionHasErrors([
                'break1_in' => '休憩時間が不適切な値です',
            ]);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $att = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2024-01-10',
        ]);

        $this->actingAs($admin)
            ->put("/admin/attendance/{$att->id}", [
                'clock_in'   => '09:00',
                'clock_out'  => '10:00',
                'break1_in'  => '09:30',
                'break1_out' => '11:00',
            ])
            // ★ 「休憩時間もしくは退勤時間が不適切な値です」に変更
            ->assertSessionHasErrors([
                'break1_out' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /** @test */
    public function 備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $att = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2024-01-10',
        ]);

        $this->actingAs($admin)
            ->put("/admin/attendance/{$att->id}", [
                'clock_in'  => '09:00',
                'clock_out' => '18:00',
                'note'      => '',
            ])
            ->assertSessionHasErrors(['note']);
    }
}