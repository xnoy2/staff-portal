<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->index('date');
            $table->index('status');
            $table->index('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
            $table->index('business');
            $table->index('created_by');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index(['type', 'status']);
        });

        Schema::table('van_allocations', function (Blueprint $table) {
            $table->index('van_id');
            $table->index(['allocated_from', 'allocated_to']);
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['project_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['business']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['type', 'status']);
        });

        Schema::table('van_allocations', function (Blueprint $table) {
            $table->dropIndex(['van_id']);
            $table->dropIndex(['allocated_from', 'allocated_to']);
        });
    }
};
