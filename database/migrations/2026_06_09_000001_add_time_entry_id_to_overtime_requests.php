<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->string('time_entry_id', 36)->nullable()->after('user_id');
            $table->foreign('time_entry_id')->references('id')->on('time_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropForeign(['time_entry_id']);
            $table->dropColumn('time_entry_id');
        });
    }
};
