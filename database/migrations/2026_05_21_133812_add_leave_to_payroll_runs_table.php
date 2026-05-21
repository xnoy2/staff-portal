<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('leave_days', 5, 1)->default(0)->after('net_pay');
            $table->json('leave_snapshot')->nullable()->after('leave_days');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['leave_days', 'leave_snapshot']);
        });
    }
};
