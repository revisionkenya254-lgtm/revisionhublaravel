<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_requests', function (Blueprint $table) {
            $table->boolean('correction_used')->default(false)->after('extra_information');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_requests', function (Blueprint $table) {
            $table->dropColumn('correction_used');
        });
    }
};
