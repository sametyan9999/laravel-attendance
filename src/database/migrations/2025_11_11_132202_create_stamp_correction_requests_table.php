<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // 勤怠が消えたら申請も消える
            $table->timestamp('requested_clock_in_at')->nullable();
            $table->timestamp('requested_clock_out_at')->nullable();
            $table->unsignedInteger('requested_break_minutes')->nullable();
            $table->string('requested_note', 255)->nullable(); // UIでは必須だがDBは寛容に
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // 申請者ユーザー削除は原則不可
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete(); // 承認者が消えた場合はNULL
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'status']);
            $table->index(['requested_by']);
            $table->index(['approved_by']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
};