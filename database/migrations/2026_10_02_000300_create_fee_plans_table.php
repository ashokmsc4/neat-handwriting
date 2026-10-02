<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20); // monthly | term | pack | one_time
            $table->decimal('amount', 10, 2);
            // Number of classes for a class pack.
            $table->unsignedSmallInteger('classes_count')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_plans');
    }
};
