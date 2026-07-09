<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->default('non_compete'); // non_compete, confidentiality, general_tc
            $table->string('title');
            $table->longText('body');                    // snapshot of the exact terms shown at issue
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedSmallInteger('duration_years')->nullable(); // e.g. 4
            $table->unsignedSmallInteger('radius_miles')->nullable();   // e.g. 50
            $table->string('status', 20)->default('pending');           // pending, acknowledged, declined
            $table->foreignUuid('issued_by')->constrained('users');
            $table->timestamp('issued_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_ip', 45)->nullable();
            $table->string('acknowledged_name')->nullable();            // typed signature
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_agreements');
    }
};
