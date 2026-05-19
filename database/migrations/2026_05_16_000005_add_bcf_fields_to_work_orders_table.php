<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('bcf_order_id')->nullable()->after('project_id');
            $table->string('bcf_stage_id')->nullable()->after('bcf_order_id');
            $table->string('bcf_order_number')->nullable()->after('bcf_stage_id');
            $table->string('bcf_stage_label')->nullable()->after('bcf_order_number');

            $table->index('bcf_stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['bcf_stage_id']);
            $table->dropColumn(['bcf_order_id', 'bcf_stage_id', 'bcf_order_number', 'bcf_stage_label']);
        });
    }
};
