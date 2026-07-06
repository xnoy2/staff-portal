<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('log_date');
            $table->string('status')->default('draft'); // draft | submitted
            $table->text('summary')->nullable();
            $table->text('blockers')->nullable();
            $table->text('plan_tomorrow')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // Manager acknowledgement
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('manager_comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'log_date']);
            $table->index(['log_date', 'status']);
        });

        Schema::create('activity_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('daily_log_id')->constrained('daily_logs')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('description');
            $table->string('category')->default('other');
            $table->foreignUuid('job_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->json('photos')->nullable(); // [{ path, name, size }]
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('daily_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_entries');
        Schema::dropIfExists('daily_logs');
    }
};
