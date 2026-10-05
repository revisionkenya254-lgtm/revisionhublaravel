<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('wishlistable_type');
            $table->unsignedBigInteger('wishlistable_id');
            $table->timestamps();

            $table->unique(['user_id', 'wishlistable_type', 'wishlistable_id'], 'wishlist_items_unique_item');
            $table->index(['wishlistable_type', 'wishlistable_id']);
        });

        if (Schema::hasTable('favorite_course_user')) {
            $legacyFavorites = DB::table('favorite_course_user')->get([
                'user_id',
                'course_id',
                'created_at',
                'updated_at',
            ]);

            foreach ($legacyFavorites->chunk(500) as $favorites) {
                DB::table('wishlist_items')->insertOrIgnore($favorites->map(fn ($favorite) => [
                    'user_id'           => $favorite->user_id,
                    'wishlistable_type' => Course::class,
                    'wishlistable_id'   => $favorite->course_id,
                    'created_at'        => $favorite->created_at,
                    'updated_at'        => $favorite->updated_at,
                ])->all());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
