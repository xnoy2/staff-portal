<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_entry_breaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('time_entry_id')->constrained('time_entries')->cascadeOnDelete();
            $table->enum('type', ['break', 'lunch']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entry_breaks');
    }
};
