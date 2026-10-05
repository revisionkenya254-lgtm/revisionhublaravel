<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select('id', 'metadata')
            ->where('type', 'past_paper')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $metadata = json_decode($product->metadata, true);

                    if (! is_array($metadata)) {
                        continue;
                    }

                    $cleanedMetadata = array_diff_key($metadata, array_flip(['topic', 'sub_topic', 'note_visibility']));

                    if ($cleanedMetadata === $metadata) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'metadata' => json_encode($cleanedMetadata, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Intentionally left blank.
        // The removed note-specific metadata cannot be reconstructed reliably.
    }
};
