<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id', 20)->nullable()->unique()->after('name');
        });

        // Backfill existing users ordered by creation date
        $users = DB::table('users')->orderBy('created_at')->pluck('id');
        foreach ($users as $index => $id) {
            DB::table('users')->where('id', $id)->update([
                'employee_id' => 'STAFF' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }
};
