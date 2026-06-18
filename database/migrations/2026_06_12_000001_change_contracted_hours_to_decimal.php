<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Allow part-hour contracts (e.g. 37.5h/week). Was unsignedTinyInteger.
            $table->decimal('contracted_hours', 5, 2)->unsigned()->default(40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('contracted_hours')->default(40)->change();
        });
    }
};
