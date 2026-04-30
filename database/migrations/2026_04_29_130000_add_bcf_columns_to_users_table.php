<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->date('hire_date')->nullable()->after('must_change_password');
            $table->string('emergency_contact_name')->nullable()->after('hire_date');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->json('certifications')->nullable()->after('emergency_contact_phone');
            $table->text('notes')->nullable()->after('certifications');
            $table->string('avatar')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'must_change_password',
                'hire_date',
                'emergency_contact_name',
                'emergency_contact_phone',
                'certifications',
                'notes',
                'avatar',
            ]);
        });
    }
};
