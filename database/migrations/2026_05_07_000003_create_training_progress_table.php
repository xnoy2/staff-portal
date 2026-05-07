<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_progress')) {
            return;
        }

        Schema::create('training_progress', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36);
            $table->uuid('lesson_id');
            $table->timestamp('completed_at')->nullable();
            $table->unique(['user_id', 'lesson_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_progress');
    }
};
