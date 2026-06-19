<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_cards', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->timestamp('due_date')->nullable()->after('description');
            $table->boolean('due_done')->default(false)->after('due_date');
            $table->foreignUuid('created_by')->nullable()->after('due_done')->constrained('users')->nullOnDelete();
        });

        // Board-level label palette (Trello-style)
        Schema::create('board_labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('color', 20);
            $table->string('name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('board_card_label', function (Blueprint $table) {
            $table->foreignUuid('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->foreignUuid('label_id')->constrained('board_labels')->cascadeOnDelete();
            $table->primary(['card_id', 'label_id']);
        });

        Schema::create('card_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('card_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->constrained('board_cards')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['card_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_comments');
        Schema::dropIfExists('card_attachments');
        Schema::dropIfExists('board_card_label');
        Schema::dropIfExists('board_labels');

        Schema::table('board_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['description', 'due_date', 'due_done']);
        });
    }
};
