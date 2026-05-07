<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_progress')) {
            return;
        }

        // Truncate any bad rows (bigint 0 stored from previous UUID truncation)
        DB::table('training_progress')->where('user_id', 0)->delete();

        Schema::table('training_progress', function (Blueprint $table) {
            $table->char('user_id', 36)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('training_progress')) {
            return;
        }

        Schema::table('training_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });
    }
};
