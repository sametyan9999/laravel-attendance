<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffInfoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 共通: 管理者ユーザーを作成
     */
    private function createAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 共通: 一般ユーザーを作成
     */
    private function createUser(string $name = '一般ユーザー'): User
    {
        return User::factory()->create([
            'role'              => 'user',
            'name'              => $name,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     *
     * 1. 管理者でログインする
     * 2. スタッフ一覧ページを開く
     * → 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
     *
     * @test
     */
    public function 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = $this->createAdmin();

        // 一般ユーザーを複数作成
        $users = User::factory()->count(3)->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.index'));

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response
                ->assertSeeText($user->name)
                ->assertSeeText($user->email);
        }
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     *
     * 1. 管理者ユーザーでログインする
     * 2. 選択したユーザーの勤怠一覧ページを開く
     * → 勤怠情報が正確に表示される
     *
     * @test
     */
    public function ユーザーの勤怠情報が正しく表示される()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('山田太郎');

        // 2024-01-10 の勤怠（09:00〜18:00、休憩 1 時間）を作成
        $workDate   = Carbon::parse('2024-01-10');
        $clockInAt  = $workDate->copy()->setTime(9, 0);
        $clockOutAt = $workDate->copy()->setTime(18, 0);

        /** @var Attendance $attendance */
        $attendance = Attendance::factory()->for($user)->create([
            'work_date'    => $workDate->toDateString(),
            'clock_in_at'  => $clockInAt,
            'clock_out_at' => $clockOutAt,
        ]);

        // 12:00〜13:00 の休憩 1 時間
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_in_at'   => $workDate->copy()->setTime(12, 0),
            'break_out_at'  => $workDate->copy()->setTime(13, 0),
        ]);

        // 対象月は 2024-01
        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.by_user', [
                'user'  => $user->id,
                'month' => '2024-01',
            ]));

        $response->assertStatus(200);

        // タイトルにユーザー名と「2024年1月」が含まれている
        $response->assertSeeText('山田太郎 さんの 2024年1月 の勤怠');

        // テーブルに日付・出勤・退勤・休憩・合計が正しく出ている
        // 日付は 01/10（木）形式。曜日までは厳密に見ず "01/10" を確認
        $response
            ->assertSee('01/10')
            ->assertSee('09:00')
            ->assertSee('18:00')
            // 休憩 60 分 → 「1:00」
            ->assertSee('1:00')
            // 実働 8 時間 → 「8:00」
            ->assertSee('8:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「前月」ボタンを押す
     * → 前月の情報が表示されている
     *
     * @test
     */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('前月テスト');

        // 前月: 2024-05、当月: 2024-06 の想定
        $prevMonthDate = Carbon::parse('2024-05-10');
        $clockInPrev   = $prevMonthDate->copy()->setTime(9, 0);
        $clockOutPrev  = $prevMonthDate->copy()->setTime(18, 0);

        Attendance::factory()->for($user)->create([
            'work_date'    => $prevMonthDate->toDateString(),
            'clock_in_at'  => $clockInPrev,
            'clock_out_at' => $clockOutPrev,
        ]);

        // 「前月」ボタン押下後に相当する URL（?month=2024-05）
        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.by_user', [
                'user'  => $user->id,
                'month' => '2024-05',
            ]));

        $response->assertStatus(200);

        // タイトルで 2024年5月 が表示されている
        $response->assertSeeText('2024年5月');

        // テーブルに 05/10 と出勤・退勤時刻が出ている
        $response
            ->assertSee('05/10')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「翌月」ボタンを押す
     * → 翌月の情報が表示されている
     *
     * @test
     */
    public function 翌月を押下した時に表示月の翌月の情報が表示されている()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('翌月テスト');

        // 翌月: 2024-06 のデータを作成
        $nextMonthDate = Carbon::parse('2024-06-15');
        $clockInNext   = $nextMonthDate->copy()->setTime(10, 0);
        $clockOutNext  = $nextMonthDate->copy()->setTime(19, 0);

        Attendance::factory()->for($user)->create([
            'work_date'    => $nextMonthDate->toDateString(),
            'clock_in_at'  => $clockInNext,
            'clock_out_at' => $clockOutNext,
        ]);

        // 「翌月」ボタン押下後に相当する URL（?month=2024-06）
        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.by_user', [
                'user'  => $user->id,
                'month' => '2024-06',
            ]));

        $response->assertStatus(200);

        // タイトルで 2024年6月 が表示されている
        $response->assertSeeText('2024年6月');

        // テーブルに 06/15 と出勤・退勤時刻が出ている
        $response
            ->assertSee('06/15')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「詳細」ボタンを押下する
     * → その日の勤怠詳細画面に遷移する
     *
     * @test
     */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('詳細テスト');

        $workDate = Carbon::parse('2024-02-20');

        /** @var Attendance $attendance */
        $attendance = Attendance::factory()->for($user)->create([
            'work_date'    => $workDate->toDateString(),
            'clock_in_at'  => $workDate->copy()->setTime(9, 0),
            'clock_out_at' => $workDate->copy()->setTime(18, 0),
        ]);

        // 一覧画面に「詳細」リンクが含まれていること
        $indexResponse = $this->actingAs($admin)
            ->get(route('admin.attendance.by_user', [
                'user'  => $user->id,
                'month' => $workDate->format('Y-m'),
            ]));

        $indexResponse->assertStatus(200);

        // リンク先 URL（/admin/attendance/{id}）が埋め込まれているか
        $detailUrl = route('admin.attendance.detail', ['attendance' => $attendance->id], false);
        $indexResponse->assertSee($detailUrl);

        // 実際に詳細ページを開いたとき、対象の勤怠が表示されること
        $detailResponse = $this->actingAs($admin)
            ->get(route('admin.attendance.detail', ['attendance' => $attendance->id]));

        $detailResponse->assertStatus(200);
        $detailResponse
            ->assertSeeText($user->name)
            ->assertSeeText($workDate->format('Y年'))
            ->assertSee('9:00', false);  // 「09:00」でもヒットするようにゆるく
    }
}