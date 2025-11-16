<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendance(User $user, string $date): Attendance
    {
        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => $date,
            'status'       => 'completed',
            'clock_in_at'  => $date . ' 09:00:00',
            'clock_out_at' => $date . ' 18:00:00',
            'note'         => '既存メモ',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_in_at'   => $date . ' 12:00:00',
            'break_out_at'  => $date . ' 13:00:00',
        ]);

        return $attendance;
    }

    // ---------------------------------------------------------
    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    // ---------------------------------------------------------
    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user, '2025-01-10');
        $this->actingAs($user);

        $response = $this->put(route('attendance.update', $attendance), [
            'clock_in'  => '18:00',
            'clock_out' => '09:00',
            'note'      => 'test',
        ]);

        $response->assertSessionHasErrors(['clock_out']);
        $this->assertDatabaseCount('stamp_correction_requests', 0);
    }

    // ---------------------------------------------------------
    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    // ---------------------------------------------------------
    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user, '2025-01-10');
        $this->actingAs($user);

        $response = $this->put(route('attendance.update', $attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '19:00',
            'break1_out'=> '20:00',
            'note'      => 'test',
        ]);

        $response->assertSessionHasErrors(['break1_in']);
        $this->assertDatabaseCount('stamp_correction_requests', 0);
    }

    // ---------------------------------------------------------
    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    // ---------------------------------------------------------
    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user, '2025-01-10');
        $this->actingAs($user);

        $response = $this->put(route('attendance.update', $attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '17:30',
            'break1_out'=> '18:30',
            'note'      => 'test',
        ]);

        $response->assertSessionHasErrors(['break1_out']);
        $this->assertDatabaseCount('stamp_correction_requests', 0);
    }

    // ---------------------------------------------------------
    // 備考欄が未入力の場合のエラーメッセージが表示される
    // ---------------------------------------------------------
    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user, '2025-01-10');
        $this->actingAs($user);

        $response = $this->put(route('attendance.update', $attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '12:00',
            'break1_out'=> '13:00',
            'note'      => '',
        ]);

        $response->assertSessionHasErrors(['note']);
        $this->assertDatabaseCount('stamp_correction_requests', 0);
    }

    // ---------------------------------------------------------
    // 修正申請処理が実行される
    // ---------------------------------------------------------
    public function test_修正申請処理が実行される()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user, '2025-01-10');
        $this->actingAs($user);

        $this->put(route('attendance.update', $attendance), [
            'clock_in'  => '09:30',
            'clock_out' => '18:30',
            'break1_in' => '12:30',
            'break1_out'=> '13:00',
            'note'      => '修正申請',
        ]);

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'requested_by'  => $user->id,
            'status'        => 'pending',
        ]);
    }

    // ---------------------------------------------------------
    // 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    // ---------------------------------------------------------
    public function test_承認待ちにログインユーザーが行った申請が全て表示されていること()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user,'2025-01-10');
        $this->actingAs($user);

        $this->put(route('attendance.update',$attendance), [
            'clock_in'=>'09:30','clock_out'=>'18:30',
            'break1_in'=>'12:30','break1_out'=>'13:00',
            'note'=>'修正申請'
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee('修正申請');
    }

    // ---------------------------------------------------------
    // 「承認済み」に管理者が承認した修正申請が全て表示されている
    // ---------------------------------------------------------
    public function test_承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user,'2025-01-10');
        $this->actingAs($user);

        $this->put(route('attendance.update',$attendance), [
            'clock_in'=>'09:30','clock_out'=>'18:30',
            'break1_in'=>'12:30','break1_out'=>'13:00',
            'note'=>'修正申請'
        ]);

        $req = StampCorrectionRequest::first();

        // 管理者が承認
        $admin = User::factory()->create([
            'role'=>'admin',
            'email_verified_at'=>now()
        ]);

        $this->actingAs($admin);
        $this->post("/admin/stamp_correction_request/{$req->id}/approve");

        $response = $this->get('/admin/stamp_correction_request/approved');
        $response->assertSee('修正申請');
    }

    // ---------------------------------------------------------
    // 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    // ---------------------------------------------------------
    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendance($user,'2025-01-10');
        $this->actingAs($user);

        $this->put(route('attendance.update',$attendance), [
            'clock_in'=>'09:30','clock_out'=>'18:30',
            'break1_in'=>'12:30','break1_out'=>'13:00',
            'note'=>'修正申請'
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee(route('attendance.detail',$attendance));
    }
}