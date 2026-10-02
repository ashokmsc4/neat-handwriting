<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named class_sessions because Laravel already uses "sessions" for logins.
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled | held | cancelled
            $table->text('topic_note')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'date', 'start_time']);
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('present'); // present | absent | late | excused
            $table->boolean('is_makeup')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['class_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('class_sessions');
    }
};
