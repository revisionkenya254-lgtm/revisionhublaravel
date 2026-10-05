<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Modules\Faq\app\Helper\FaqHelper;
use Modules\Frontend\app\Helper\SectionHelper;
use Modules\Frontend\app\Models\Section;
use Modules\Testimonial\app\Helper\TestimonialHelper;

class AboutPageController extends Controller {
    public function index(): View {
        $theme_name = Session::has('demo_theme') ? Session::get('demo_theme') : DEFAULT_HOMEPAGE;

        $sections = SectionHelper::getAll($theme_name);

        $hero = $sections->where('name', 'hero_section')->first();
        $aboutSection = $sections->where('name', 'about_section')->first();
        $ourFeatures = $sections->where('name', 'our_features_section')->first();
        $newsletterSection = $sections->where('name', 'newsletter_section')->first();

        $brands = brands();
        $reviews = TestimonialHelper::getAll();
        $faqs = FaqHelper::getAll();

        $faqSection = $sections->where('name', 'faq_section')->first();
        return view('frontend.pages.about-us', compact('aboutSection', 'ourFeatures', 'newsletterSection', 'hero', 'brands', 'reviews', 'faqSection', 'faqs'));
    }
}
