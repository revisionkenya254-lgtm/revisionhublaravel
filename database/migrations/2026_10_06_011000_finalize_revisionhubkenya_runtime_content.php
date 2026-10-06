<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $settings = [
                'app_name' => 'RevisionHubKenya',
                'copyright_text' => '2026 RevisionHubKenya. All rights reserved.',
                'mail_sender_name' => 'RevisionHubKenya',
                'maintenance_description' => '<p>We are currently performing maintenance on our website to<br>improve your experience. Please check back later.</p><p><a title="RevisionHubKenya" href="https://revisionhubkenya.com/">RevisionHubKenya</a></p>',
            ];

            foreach ($settings as $key => $value) {
                DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
            }
        }

        if (Schema::hasTable('seo_settings')) {
            $seo = [
                'home_page' => ['Home | RevisionHubKenya', 'Revision resources, past papers, and exam preparation from RevisionHubKenya.'],
                'about_page' => ['About | RevisionHubKenya', 'Learn more about RevisionHubKenya.'],
                'course_page' => ['Courses | RevisionHubKenya', 'Explore courses and revision resources from RevisionHubKenya.'],
                'blog_page' => ['Blog | RevisionHubKenya', 'Read learning and exam preparation articles from RevisionHubKenya.'],
                'contact_page' => ['Contact | RevisionHubKenya', 'Contact the RevisionHubKenya team.'],
            ];

            foreach ($seo as $pageName => [$title, $description]) {
                DB::table('seo_settings')
                    ->where('page_name', $pageName)
                    ->update(['seo_title' => $title, 'seo_description' => $description]);
            }
        }

        if (Schema::hasTable('footer_settings')) {
            DB::table('footer_settings')->update([
                'logo' => 'uploads/website-images/logo.svg',
                'footer_text' => 'Revision resources, past papers, and exam preparation for learners in Kenya.',
                'address' => 'Kenya',
                'phone' => null,
                'get_in_touch_text' => 'Connect with RevisionHubKenya for learning resources and support.',
                'google_play_link' => null,
                'apple_store_link' => null,
            ]);
        }

        $this->replaceLegacyBrand('email_templates', 'message');
        $this->replaceLegacyBrand('brands', 'url');
        $this->replaceLegacyBrand('orders', 'payment_details');
        $this->replaceLegacyBrand('users', 'website');
    }

    public function down(): void
    {
        // Ownership and branding changes are intentionally not reversible.
    }

    private function replaceLegacyBrand(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id') || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select('id', $column)
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column): void {
                foreach ($rows as $row) {
                    $original = (string) $row->{$column};
                    $branded = str_ireplace(
                        [
                            'https://revisionhubkenya.websolutionus.com',
                            'https://www.websolutionus.com',
                            'https://websolutionus.com',
                            'skillgro.com',
                            'skill_grow',
                            'skill grow',
                            'skillgrow',
                            'skillgro',
                            'websolutionus',
                            'websolutions',
                        ],
                        [
                            'https://revisionhubkenya.com',
                            'https://revisionhubkenya.com',
                            'https://revisionhubkenya.com',
                            'revisionhubkenya.com',
                            'RevisionHubKenya',
                            'RevisionHubKenya',
                            'RevisionHubKenya',
                            'RevisionHubKenya',
                            'RevisionHubKenya',
                            'RevisionHubKenya',
                        ],
                        $original
                    );

                    if ($branded !== $original) {
                        DB::table($table)->where('id', $row->id)->update([$column => $branded]);
                    }
                }
            });
    }
};
