<?php

namespace Modules\GlobalSetting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\GlobalSetting\app\Models\SeoSetting;

class SeoInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item1 = new SeoSetting();
        $item1->page_name = 'home_page';
        $item1->seo_title = 'Home | RevisionHubKenya';
        $item1->seo_description = 'Revision resources, past papers, and exam preparation from RevisionHubKenya.';
        $item1->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'about_page';
        $item2->seo_title = 'About | RevisionHubKenya';
        $item2->seo_description = 'Learn more about RevisionHubKenya.';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'course_page';
        $item2->seo_title = 'Courses | RevisionHubKenya';
        $item2->seo_description = 'Explore courses and revision resources from RevisionHubKenya.';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'blog_page';
        $item2->seo_title = 'Blog | RevisionHubKenya';
        $item2->seo_description = 'Read learning and exam preparation articles from RevisionHubKenya.';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'contact_page';
        $item2->seo_title = 'Contact | RevisionHubKenya';
        $item2->seo_description = 'Contact the RevisionHubKenya team.';
        $item2->save();
    }
}
