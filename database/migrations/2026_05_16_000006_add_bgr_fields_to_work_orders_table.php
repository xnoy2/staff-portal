<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('bgr_project_id')->nullable()->after('bcf_stage_label');
            $table->string('bgr_stage_id')->nullable()->after('bgr_project_id');
            $table->string('bgr_project_name')->nullable()->after('bgr_stage_id');
            $table->string('bgr_stage_label')->nullable()->after('bgr_project_name');
            $table->index('bgr_stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['bgr_stage_id']);
            $table->dropColumn(['bgr_project_id', 'bgr_stage_id', 'bgr_project_name', 'bgr_stage_label']);
        });
    }
};
