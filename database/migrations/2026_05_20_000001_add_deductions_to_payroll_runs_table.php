<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->json('deductions')->nullable()->after('entries_snapshot');
            $table->decimal('net_pay', 10, 2)->nullable()->after('gross_pay');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['deductions', 'net_pay']);
        });
    }
};
