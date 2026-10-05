<?php

namespace Modules\Language\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Language\app\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_default' => true]
        );

        Language::updateOrCreate(
            ['code' => 'hi'],
            ['name' => 'Hindi', 'is_default' => false]
        );

        Language::updateOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'direction' => 'rtl', 'is_default' => false]
        );
    }
}
