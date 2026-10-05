<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Language\app\Enums\AllCountriesDetailsEnum;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = AllCountriesDetailsEnum::getAll()->map(function (object $country) use ($now) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('countries')->upsert($rows, ['id'], ['name', 'status', 'updated_at']);
    }

    public function down(): void
    {
        // Preserve user data on rollback.
    }
};
