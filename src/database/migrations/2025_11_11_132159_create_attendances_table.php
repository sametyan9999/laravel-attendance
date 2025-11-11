<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // ユーザー削除は原則させない運用想定
            $table->date('work_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->enum('status', ['off_duty','working','break','completed'])->default('off_duty');
            $table->timestamps();

            $table->unique(['user_id', 'work_date']); // 1ユーザー1日1レコード
            $table->index(['user_id', 'work_date']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};