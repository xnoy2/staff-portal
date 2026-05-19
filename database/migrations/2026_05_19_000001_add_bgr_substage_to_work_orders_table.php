<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('bgr_substage_id')->nullable()->after('bgr_stage_label');
            $table->string('bgr_substage_label')->nullable()->after('bgr_substage_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['bgr_substage_id', 'bgr_substage_label']);
        });
    }
};
