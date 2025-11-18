<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function createUser(string $name = '一般ユーザー'): User
    {
        return User::factory()->create([
            'role'              => 'user',
            'name'              => $name,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 承認待ちの修正申請が全て表示されている
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請一覧ページを開き、承認待ちのタブを開く
     * → 全ユーザーの未承認の修正申請が表示される
     *
     * @test
     */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser('ユーザー1');
        $user2 = $this->createUser('ユーザー2');

        $att1 = Attendance::factory()->for($user1)->create();
        $att2 = Attendance::factory()->for($user2)->create();

        // 承認待ち申請（2件）
        $pending1 = StampCorrectionRequest::factory()->create([
            'attendance_id'           => $att1->id,
            'requested_by'            => $user1->id,
            'status'                  => 'pending',
            'requested_note'          => 'ユーザー1の申請',
            'requested_break_minutes' => 60,
        ]);

        $pending2 = StampCorrectionRequest::factory()->create([
            'attendance_id'           => $att2->id,
            'requested_by'            => $user2->id,
            'status'                  => 'pending',
            'requested_note'          => 'ユーザー2の申請',
            'requested_break_minutes' => 30,
        ]);

        // 承認済み申請（一覧に出てほしくない）
        $approved = StampCorrectionRequest::factory()->create([
            'attendance_id'  => $att1->id,
            'requested_by'   => $user1->id,
            'status'         => 'approved',
            'requested_note' => '承認済みの申請',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.request.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSeeText('承認待ち');

        // 承認待ち 2 件分が表示されている
        $response
            ->assertSeeText('ユーザー1の申請')
            ->assertSeeText('ユーザー2の申請');

        // 承認済みの申請は含まれていない
        $response->assertDontSeeText('承認済みの申請');
    }

    /**
     * 承認済みの修正申請が全て表示されている
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請一覧ページを開き、承認済みのタブを開く
     * → 全ユーザーの承認済みの修正申請が表示される
     *
     * @test
     */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('ユーザーA');

        $att = Attendance::factory()->for($user)->create();

        $approved1 = StampCorrectionRequest::factory()->create([
            'attendance_id'  => $att->id,
            'requested_by'   => $user->id,
            'status'         => 'approved',
            'requested_note' => '承認済み申請1',
        ]);

        $approved2 = StampCorrectionRequest::factory()->create([
            'attendance_id'  => $att->id,
            'requested_by'   => $user->id,
            'status'         => 'approved',
            'requested_note' => '承認済み申請2',
        ]);

        // pending は含まれないことを確認するために 1 件作成
        $pending = StampCorrectionRequest::factory()->create([
            'attendance_id'  => $att->id,
            'requested_by'   => $user->id,
            'status'         => 'pending',
            'requested_note' => '未承認申請',
        ]);

        // 承認済みタブ
        $response = $this->actingAs($admin)
            ->get(route('admin.request.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSeeText('承認済み');

        $response
            ->assertSeeText('承認済み申請1')
            ->assertSeeText('承認済み申請2');

        $response->assertDontSeeText('未承認申請');
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請の詳細画面を開く
     * → 申請内容が正しく表示されている
     *
     * @test
     */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('申請ユーザー');

        $workDate = Carbon::parse('2024-03-05');
        $clockIn  = $workDate->copy()->setTime(9, 0);
        $clockOut = $workDate->copy()->setTime(18, 0);

        /** @var Attendance $attendance */
        $attendance = Attendance::factory()->for($user)->create([
            'work_date'    => $workDate->toDateString(),
            'clock_in_at'  => $clockIn,
            'clock_out_at' => $clockOut,
        ]);

        /** @var StampCorrectionRequest $request */
        $request = StampCorrectionRequest::factory()->create([
            'attendance_id'           => $attendance->id,
            'requested_by'            => $user->id,
            'status'                  => 'pending',
            'requested_clock_in_at'   => $clockIn,
            'requested_clock_out_at'  => $clockOut,
            'requested_break_minutes' => 90,
            'requested_note'          => 'テストの修正理由',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.request.show', [
                'attendance_correct_request_id' => $request->id,
            ]));

        $response->assertStatus(200);

        // 名前
        $response->assertSeeText('申請ユーザー');

        // 日付（年と日付部分を分けて出しているので両方見る）
        $response
            ->assertSeeText('2024年')
            ->assertSeeText('3月5日');

        // 出勤・退勤（H:i）
        $response
            ->assertSee('09:00')
            ->assertSee('18:00');

        // 休憩 90 分 → 01:30
        $response->assertSee('01:30');

        // 備考
        $response->assertSeeText('テストの修正理由');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請の詳細画面で「承認」ボタンを押す
     * → 修正申請が承認され、勤怠情報が更新される
     *
     * @test
     */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = $this->createAdmin();
        $user  = $this->createUser('承認ユーザー');

        $workDate = Carbon::parse('2024-04-01');

        /** @var Attendance $attendance */
        $attendance = Attendance::factory()->for($user)->create([
            'work_date'    => $workDate->toDateString(),
            // 元の勤怠は空（もしくは別の値）としておく
            'clock_in_at'  => $workDate->copy()->setTime(8, 0),
            'clock_out_at' => $workDate->copy()->setTime(17, 0),
        ]);

        // 申請で 09:00〜18:00、休憩 60 分 に修正する
        $requestedClockIn  = $workDate->copy()->setTime(9, 0);
        $requestedClockOut = $workDate->copy()->setTime(18, 0);

        /** @var StampCorrectionRequest $request */
        $request = StampCorrectionRequest::factory()->create([
            'attendance_id'           => $attendance->id,
            'requested_by'            => $user->id,
            'status'                  => 'pending',
            'requested_clock_in_at'   => $requestedClockIn,
            'requested_clock_out_at'  => $requestedClockOut,
            'requested_break_minutes' => 60,
            'requested_note'          => '承認テスト',
        ]);

        // 承認ボタン押下（POST）
        $response = $this->actingAs($admin)
            ->post(route('admin.request.approve', [
                'attendance_correct_request_id' => $request->id,
            ]), [
                'note' => '管理者メモ',
            ]);

        // 承認後は詳細画面へリダイレクトされる想定
        $response->assertRedirect(
            route('admin.request.show', [
                'attendance_correct_request_id' => $request->id,
            ])
        );

        // DB の状態を再取得
        $attendance->refresh();
        $request->refresh();

        // 申請ステータスが approved になっている
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->approved_by);
        $this->assertNotNull($request->approved_at);

        // 勤怠の出勤・退勤が申請値に更新されている
        $this->assertTrue($attendance->clock_in_at->equalTo($requestedClockIn));
        $this->assertTrue($attendance->clock_out_at->equalTo($requestedClockOut));

        // 休憩1件分が作られ、合計 60 分になっていること
        $attendance->load('breaks');
        $this->assertCount(1, $attendance->breaks);
        $break = $attendance->breaks->first();
        $this->assertEquals(
            60,
            $break->break_out_at->diffInMinutes($break->break_in_at)
        );
    }
}