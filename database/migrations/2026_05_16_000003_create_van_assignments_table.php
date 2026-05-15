<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('van_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('van_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('returned_at')->nullable();
            $table->foreignUuid('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['van_id', 'returned_at']);
            $table->index(['user_id', 'returned_at']);
        });

        // Migrate existing van_user pivot records to van_assignments
        // Each existing assignment becomes an active record (returned_at = null)
        if (Schema::hasTable('van_user')) {
            $existing = DB::table('van_user')->get();
            foreach ($existing as $row) {
                DB::table('van_assignments')->insert([
                    'van_id'      => $row->van_id,
                    'user_id'     => $row->user_id,
                    'assigned_by' => null,
                    'assigned_at' => $row->assigned_at ?? now(),
                    'returned_at' => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('van_assignments');
    }
};
