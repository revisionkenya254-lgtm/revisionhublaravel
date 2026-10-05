<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configurations')->updateOrInsert(
            ['config' => 'setup_complete'],
            ['value' => 1, 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('configurations')->updateOrInsert(
            ['config' => 'setup_stage'],
            ['value' => 5, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('configurations')
            ->whereIn('config', ['setup_complete', 'setup_stage'])
            ->delete();
    }
};
