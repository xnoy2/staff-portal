<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The earlier per-day task list was never shipped; drop it if present.
        Schema::dropIfExists('staff_tasks');

        Schema::create('boards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
        });

        Schema::create('board_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['board_id', 'sort_order']);
        });

        Schema::create('board_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('list_id')->constrained('board_lists')->cascadeOnDelete();
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['list_id', 'sort_order']);
        });

        Schema::create('card_checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['card_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_checklist_items');
        Schema::dropIfExists('board_cards');
        Schema::dropIfExists('board_lists');
        Schema::dropIfExists('boards');
    }
};
