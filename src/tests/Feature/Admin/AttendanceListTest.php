<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function その日に出された全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $u1->id,
            'work_date' => '2024-01-10',
        ]);
        Attendance::factory()->create([
            'user_id' => $u2->id,
            'work_date' => '2024-01-10',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2024-01-10')
            ->assertStatus(200)
            ->assertSee($u1->name)
            ->assertSee($u2->name);
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $today = Carbon::today()->format('Y/m/d');

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertStatus(200)
            ->assertSee($today);
    }

    /** @test */
    public function 前日_を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $target = Carbon::parse('2024-01-10');
        $prev = $target->copy()->subDay()->format('Y/m/d');

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2024-01-10')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2024-01-09')
            ->assertStatus(200)
            ->assertSee($prev);
    }

    /** @test */
    public function 翌日_を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $target = Carbon::parse('2024-01-10');
        $next = $target->copy()->addDay()->format('Y/m/d');

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2024-01-11')
            ->assertStatus(200)
            ->assertSee($next);
    }
}