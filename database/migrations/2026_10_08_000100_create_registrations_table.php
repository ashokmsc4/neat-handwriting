<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sign-ups sent by parents through the public registration link,
        // kept apart from students until the teacher accepts them.
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('child_name');
            $table->date('dob')->nullable();
            $table->string('school')->nullable();
            $table->string('grade', 30)->nullable();
            $table->json('course_ids')->nullable();
            $table->string('preferred_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('guardian_name');
            $table->string('phone', 20);
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('photo_consent')->default(false);
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
