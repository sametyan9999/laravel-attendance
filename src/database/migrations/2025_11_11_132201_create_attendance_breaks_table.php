<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // 勤怠削除時は休憩も削除
            $table->timestamp('break_in_at')->nullable();
            $table->timestamp('break_out_at')->nullable();
            $table->timestamps();

            $table->index(['attendance_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};