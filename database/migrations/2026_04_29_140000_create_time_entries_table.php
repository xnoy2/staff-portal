<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->char('job_id', 36)->nullable()->index();
            $table->char('van_id', 36)->nullable()->index();
            $table->datetime('clock_in');
            $table->datetime('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('source', ['self_clockin', 'site_head', 'manual', 'bulk'])->default('self_clockin');
            $table->foreignUuid('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'clock_in']);
            $table->index(['status', 'clock_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
