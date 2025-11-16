<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用ユーザー作成（メール認証済み）
     */
    private function createVerifiedUser(): User
    {
        $now = CarbonImmutable::now();
        return User::factory()->create([
            'email_verified_at' => $now,
        ]);
    }

    // -----------------------------------------------------------------
    // ID4 日時取得機能
    // -----------------------------------------------------------------

    /**
     * ID4-1 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_現在の日時情報がUIと同じ形式で出力されている()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 30, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $nowJp = $fixedNow->copy()
            ->timezone(config('app.timezone'))
            ->locale('ja');

        $expectedDate = $nowJp->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = $nowJp->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }

    // -----------------------------------------------------------------
    // ID5 ステータス確認機能
    // -----------------------------------------------------------------

    /**
     * ID5-1 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_勤務外の場合_勤怠ステータスが正しく表示される()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * ID5-2 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_出勤中の場合_勤怠ステータスが正しく表示される()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * ID5-3 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_休憩中の場合_勤怠ステータスが正しく表示される()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 10, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $today,
            'status'    => 'break',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * ID5-4 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_退勤済の場合_勤怠ステータスが正しく表示される()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 18, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => $today,
            'status'       => 'completed',
            'clock_in_at'  => $fixedNow->copy()->subHours(9),
            'clock_out_at' => $fixedNow,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    // -----------------------------------------------------------------
    // ID6 出勤機能
    // -----------------------------------------------------------------

    /**
     * ID6-1 出勤ボタンが正しく機能する
     */
    public function test_出勤ボタンが正しく機能する()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)
            ->from('/attendance')
            ->post(route('attendance.clock_in'));

        $response->assertStatus(302);
        $response->assertSessionHas('ok', true);

        /** @var Attendance $attendance */
        $attendance = Attendance::first();
        $this->assertNotNull($attendance);
        $this->assertEquals('working', $attendance->status);
        $this->assertEquals(
            $fixedNow->toDateTimeString(),
            optional($attendance->clock_in_at)->toDateTimeString()
        );
    }

    /**
     * ID6-2 出勤は一日一回のみできる
     */
    public function test_出勤は一日一回のみできる()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow,
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance')
            ->post(route('attendance.clock_in'));

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'clock_in' => '既に出勤済みです。',
        ]);

        $this->assertEquals(1, Attendance::count());
    }

    /**
     * ID6-3 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user = $this->createVerifiedUser();

        $this->actingAs($user)->post(route('attendance.clock_in'));

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2025-01']));

        $response->assertStatus(200);

        $expectedTime = $fixedNow->format('H:i');
        $response->assertSee($expectedTime);
    }

    // -----------------------------------------------------------------
    // ID7 休憩機能
    // -----------------------------------------------------------------

    /**
     * ID7-1 休憩ボタンが正しく機能する
     */
    public function test_休憩ボタンが正しく機能する()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 10, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow->copy()->subHour(),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance')
            ->post(route('attendance.break_in'));

        $response->assertStatus(302);

        $attendance = Attendance::first();
        $this->assertEquals('break', $attendance->status);
        $this->assertEquals(1, AttendanceBreak::where('attendance_id', $attendance->id)->count());
    }

    /**
     * ID7-2 休憩は一日何回でもできる
     */
    public function test_休憩は一日何回でもできる()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 10, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow->copy()->subHours(1),
        ]);

        $this->actingAs($user);

        // 1回目
        $this->post(route('attendance.break_in'));
        $this->post(route('attendance.break_out'));

        // 2回目
        $this->post(route('attendance.break_in'));
        $this->post(route('attendance.break_out'));

        $this->assertEquals(
            2,
            AttendanceBreak::where('attendance_id', $attendance->id)->count()
        );
    }

    /**
     * ID7-3 休憩戻ボタンが正しく機能する
     */
    public function test_休憩戻ボタンが正しく機能する()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 10, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow->copy()->subHour(),
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.break_in'));
        $attendance->refresh();
        $this->assertEquals('break', $attendance->status);

        $this->post(route('attendance.break_out'));
        $attendance->refresh();

        $this->assertEquals('working', $attendance->status);
    }

    /**
     * ID7-4 休憩戻は一日何回でもできる
     */
    public function test_休憩戻は一日何回でもできる()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 10, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow->copy()->subHours(1),
        ]);

        $this->actingAs($user);

        // 1回目
        $this->post(route('attendance.break_in'));
        $this->post(route('attendance.break_out'));

        // 2回目
        $this->post(route('attendance.break_in'));
        $this->post(route('attendance.break_out'));

        $attendance->refresh();
        $this->assertEquals('working', $attendance->status);

        $completedBreaks = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNotNull('break_in_at')
            ->whereNotNull('break_out_at')
            ->count();

        $this->assertEquals(2, $completedBreaks);
    }

    /**
     * ID7-5 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_休憩時刻が勤怠一覧画面で確認できる()
    {
        // 9:00 出勤 → 12:00 休憩入 → 12:30 休憩戻 の想定
        $base = CarbonImmutable::create(2025, 1, 15, 9, 0, 0, 'Asia/Tokyo');

        // 出勤時刻
        CarbonImmutable::setTestNow($base);
        Carbon::setTestNow($base);

        $user  = $this->createVerifiedUser();
        $today = $base->toDateString();

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $base,
        ]);

        $this->actingAs($user);

        // 12:00 に休憩入
        $breakIn = $base->copy()->addHours(3);
        CarbonImmutable::setTestNow($breakIn);
        Carbon::setTestNow($breakIn);
        $this->post(route('attendance.break_in'));

        // 12:30 に休憩戻
        $breakOut = $breakIn->addMinutes(30);
        CarbonImmutable::setTestNow($breakOut);
        Carbon::setTestNow($breakOut);
        $this->post(route('attendance.break_out'));

        $break = AttendanceBreak::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($break);

        // 月次一覧で休憩時間が表示されていることを確認
        $response = $this->get(route('attendance.list', ['month' => '2025-01']));
        $response->assertStatus(200);

        // 実際の break_in/out から休憩分数を計算し、H:MM 形式に変換
        $minutes = Carbon::parse($break->break_out_at)
            ->diffInMinutes(Carbon::parse($break->break_in_at));
        $expectedBreak = sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);

        $response->assertSee($expectedBreak);
    }

    // -----------------------------------------------------------------
    // ID8 退勤機能
    // -----------------------------------------------------------------

    /**
     * ID8-1 退勤ボタンが正しく機能する
     */
    public function test_退勤ボタンが正しく機能する()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 18, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user  = $this->createVerifiedUser();
        $today = $fixedNow->toDateString();

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'work_date'   => $today,
            'status'      => 'working',
            'clock_in_at' => $fixedNow->copy()->subHours(9),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance')
            ->post(route('attendance.clock_out'));

        $response->assertStatus(302);
        $response->assertSessionHas('ok', true);

        $attendance->refresh();
        $this->assertEquals('completed', $attendance->status);
        $this->assertNotNull($attendance->clock_out_at);
    }

    /**
     * ID8-2 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        $fixedNow = CarbonImmutable::create(2025, 1, 15, 18, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($fixedNow);
        Carbon::setTestNow($fixedNow);

        $user = $this->createVerifiedUser();

        $this->actingAs($user)->post(route('attendance.clock_in'));
        $this->post(route('attendance.clock_out'));

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2025-01']));

        $response->assertStatus(200);

        $expectedOut = $fixedNow->format('H:i');
        $response->assertSee($expectedOut);
    }
}