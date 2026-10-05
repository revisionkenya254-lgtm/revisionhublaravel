<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $normalize = static function (?string $value): ?string {
            return in_array($value, ['Lower Primary', 'Upper Primary', 'lower-primary', 'upper-primary'], true)
                ? 'Primary'
                : $value;
        };

        if (Schema::hasColumn('products', 'education_level')) {
            DB::table('products')
                ->whereIn('education_level', ['Lower Primary', 'Upper Primary', 'lower-primary', 'upper-primary'])
                ->update(['education_level' => 'Primary']);
        }

        DB::table('products')
            ->select('id', 'metadata')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($normalize) {
                foreach ($products as $product) {
                    $metadata = json_decode($product->metadata, true);

                    if (! is_array($metadata) || ! array_key_exists('education_level', $metadata)) {
                        continue;
                    }

                    $normalizedLevel = $normalize(is_string($metadata['education_level']) ? $metadata['education_level'] : null);

                    if ($normalizedLevel === $metadata['education_level']) {
                        continue;
                    }

                    $metadata['education_level'] = $normalizedLevel;

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE)]);
                }
            }, 'id');
    }

    public function down(): void
    {
        $denormalize = static function (?string $value): ?string {
            return $value === 'Primary' ? 'Lower Primary' : $value;
        };

        if (Schema::hasColumn('products', 'education_level')) {
            DB::table('products')
                ->where('education_level', 'Primary')
                ->update(['education_level' => 'Lower Primary']);
        }

        DB::table('products')
            ->select('id', 'metadata')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($denormalize) {
                foreach ($products as $product) {
                    $metadata = json_decode($product->metadata, true);

                    if (! is_array($metadata) || ! array_key_exists('education_level', $metadata)) {
                        continue;
                    }

                    $normalizedLevel = $denormalize(is_string($metadata['education_level']) ? $metadata['education_level'] : null);

                    if ($normalizedLevel === $metadata['education_level']) {
                        continue;
                    }

                    $metadata['education_level'] = $normalizedLevel;

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE)]);
                }
            }, 'id');
    }
};
