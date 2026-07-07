<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('plan_tomorrow'); // [{ path, name, size }]
            $table->json('jobs')->nullable()->after('photos');          // [work_order id, ...]
        });
        // Note: the activity_entries table is left in place but no longer used
        // (the day log now carries photos + job tags directly).
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn(['photos', 'jobs']);
        });
    }
};
