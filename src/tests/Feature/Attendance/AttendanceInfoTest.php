<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceInfoTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * ファクトリを使わずに勤怠＋休憩1件を作成
     */
    private function createAttendanceWithBreaks(User $user, string $workDate): Attendance
    {
        // 勤怠
        $attendance          = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->work_date    = $workDate;               // 'Y-m-d'
        $attendance->status       = 'completed';
        $attendance->clock_in_at  = $workDate . ' 09:00:00';
        $attendance->clock_out_at = $workDate . ' 18:00:00';
        $attendance->note         = '既存メモ';
        $attendance->save();

        // 休憩（12:00〜13:00）
        $break                 = new AttendanceBreak();
        $break->attendance_id  = $attendance->id;
        $break->break_in_at    = $workDate . ' 12:00:00';
        $break->break_out_at   = $workDate . ' 13:00:00';
        $break->save();

        return $attendance;
    }

    // ============================================================
    // ID9: 勤怠一覧情報取得機能（一般ユーザー）
    // ============================================================

        /** 自分が行った勤怠情報が全て表示されている */
    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $this->travelTo(CarbonImmutable::parse('2025-01-15 09:00:00'));

        $user = $this->createVerifiedUser();

        // 自分の勤怠 2 件
        $this->createAttendanceWithBreaks($user, '2025-01-10');
        $this->createAttendanceWithBreaks($user, '2025-01-11');

        // 他人の勤怠（仕様上は気にしないが、一応データを作成）
        $other = User::factory()->create(['email_verified_at' => now()]);
        $this->createAttendanceWithBreaks($other, '2025-01-10');

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertOk()
            // 日付行がある（自分の2日分）
            ->assertSee('01/10')
            ->assertSee('01/11')
            // 自分の出勤・退勤・休憩・合計が表示されている
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')   // 休憩
            ->assertSee('8:00');  // 合計
        // 他人の勤怠が表示されないことまではテストしない（仕様外のため）
    }

    /** 勤怠一覧画面に遷移した際に現在の月が表示されている */
    public function test_勤怠一覧画面に遷移した際に現在の月が表示されている()
    {
        $this->travelTo(CarbonImmutable::parse('2025-03-05 10:00:00'));

        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertOk()
                 ->assertSee('2025/03');
    }

    /** 「前月」を押下した時に表示月の前月の情報が表示される */
    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        $this->travelTo(CarbonImmutable::parse('2025-03-05 10:00:00'));

        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        // 基準月 2025-03 から前月 2025-02 を指定
        $response = $this->get(route('attendance.list', ['month' => '2025-02']));

        $response->assertOk()
                 ->assertSee('2025/02')
                 ->assertSee(route('attendance.list', ['month' => '2025-01']))
                 ->assertSee(route('attendance.list', ['month' => '2025-03']));
    }

    /** 「翌月」を押下した時に表示月の翌月の情報が表示される */
    public function test_翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $this->travelTo(CarbonImmutable::parse('2025-03-05 10:00:00'));

        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        // 基準月 2025-03 から翌月 2025-04 を指定
        $response = $this->get(route('attendance.list', ['month' => '2025-04']));

        $response->assertOk()
                 ->assertSee('2025/04')
                 ->assertSee(route('attendance.list', ['month' => '2025-03']))
                 ->assertSee(route('attendance.list', ['month' => '2025-05']));
    }

    /** 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $this->travelTo(CarbonImmutable::parse('2025-01-15 09:00:00'));

        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreaks($user, '2025-01-10');

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertOk()
                 ->assertSee(route('attendance.detail', ['attendance' => $attendance->id]));
    }

    // ============================================================
    // ID10: 勤怠詳細情報取得機能（一般ユーザー）
    // ============================================================

    /** 勤怠詳細画面の「名前」がログインユーザーの氏名になっている */
    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreaks($user, '2025-01-10');

        $this->actingAs($user);

        $response = $this->get(route('attendance.detail', $attendance));

        $response->assertOk()
                 ->assertSee($user->name);
    }

    /** 勤怠詳細画面の「日付」が選択した日付になっている */
    public function test_勤怠詳細画面の日付が選択した日付になっている()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreaks($user, '2025-02-03');

        $this->actingAs($user);

        $response = $this->get(route('attendance.detail', $attendance));

        // Blade では「YYYY年」「M月D日」で出しているので両方チェック
        $response->assertOk()
                 ->assertSee('2025年')
                 ->assertSee('2月3日');
    }

    /** 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_出勤退勤にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreaks($user, '2025-01-10');

        $this->actingAs($user);

        $response = $this->get(route('attendance.detail', $attendance));

        $response->assertOk()
                 ->assertSee('09:00')
                 ->assertSee('18:00');
    }

    /** 「休憩」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_休憩にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreaks($user, '2025-01-10');

        $this->actingAs($user);

        $response = $this->get(route('attendance.detail', $attendance));

        $response->assertOk()
                 ->assertSee('12:00')
                 ->assertSee('13:00');
    }
}