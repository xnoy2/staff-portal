<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_cards', function (Blueprint $table) {
            // Trello-style start date (date only) alongside the existing due_date.
            $table->date('start_date')->nullable()->after('description');

            // Due-date reminder: null = none, otherwise an offset key (e.g. "at_time",
            // "1_hour", "1_day"). sent_at guards against sending the same reminder twice.
            $table->string('due_reminder')->nullable()->after('due_done');
            $table->timestamp('due_reminder_sent_at')->nullable()->after('due_reminder');

            // Recurrence: never | daily | weekly | monthly. recurred_at marks when this
            // card last spawned its successor so it only ever spawns one.
            $table->string('recurring')->default('never')->after('due_reminder_sent_at');
            $table->timestamp('recurred_at')->nullable()->after('recurring');
        });
    }

    public function down(): void
    {
        Schema::table('board_cards', function (Blueprint $table) {
            $table->dropColumn([
                'start_date', 'due_reminder', 'due_reminder_sent_at', 'recurring', 'recurred_at',
            ]);
        });
    }
};
