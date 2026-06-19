<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('blue');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // owner | member
            $table->string('role', 20)->default('member');
            $table->timestamps();

            $table->primary(['workspace_id', 'user_id']);
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->nullable()->after('user_id')
                ->constrained('workspaces')->cascadeOnDelete();
        });

        // ── Backfill: fold each user's existing boards into a personal workspace ──
        $userIds = DB::table('boards')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if (! $user) {
                continue;
            }

            $first = strtok($user->name ?? 'My', ' ');
            $wsId  = (string) Str::uuid();

            DB::table('workspaces')->insert([
                'id'         => $wsId,
                'owner_id'   => $userId,
                'name'       => $first . "'s Workspace",
                'color'      => 'blue',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('workspace_members')->insert([
                'workspace_id' => $wsId,
                'user_id'      => $userId,
                'role'         => 'owner',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('boards')->where('user_id', $userId)->update(['workspace_id' => $wsId]);
        }
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
