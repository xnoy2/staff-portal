<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_module_enrollments')) {
            return;
        }

        Schema::create('training_module_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('module_id');
            $table->char('user_id', 36);
            $table->timestamps();
            $table->unique(['module_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_module_enrollments');
    }
};
