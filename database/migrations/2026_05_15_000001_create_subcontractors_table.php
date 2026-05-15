<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontractors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trade');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('qualification_verified')->default(false);
            $table->boolean('insurance_verified')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('subcontractor_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['before', 'after']);
            $table->string('path');
            $table->string('original_name');
            $table->string('caption')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontractor_photos');
        Schema::dropIfExists('subcontractors');
    }
};
