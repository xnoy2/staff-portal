<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_checklist_items', function (Blueprint $table) {
            $table->uuid('job_id')->nullable()->after('project_id');
            $table->foreign('job_id')->references('id')->on('work_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_checklist_items', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->dropColumn('job_id');
        });
    }
};
