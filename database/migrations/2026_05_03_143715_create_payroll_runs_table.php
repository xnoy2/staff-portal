<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('regular_hours',  8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('total_hours',    8, 2)->default(0);
            $table->decimal('hourly_rate',    8, 2)->nullable();
            $table->decimal('regular_pay',   10, 2)->default(0);
            $table->decimal('overtime_pay',  10, 2)->default(0);
            $table->decimal('gross_pay',     10, 2)->default(0);
            $table->unsignedSmallInteger('shifts_count')->default(0);
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('entries_snapshot');
            $table->timestamps();

            $table->unique(['user_id', 'period_from', 'period_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
