<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default fee plan for students added to the batch.
        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('fee_plan_id')->nullable()->after('level_id')->constrained()->nullOnDelete();
        });

        // "2026-10" for monthly invoices, so each month is billed only once per enrollment.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('period', 7)->nullable()->after('period_label');
            $table->string('notes')->nullable()->after('status');
            $table->unique(['enrollment_id', 'period']);
        });

        // Small key/value store for things the teacher can change in Settings.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'period']);
            $table->dropColumn(['period', 'notes']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_plan_id');
        });
    }
};
