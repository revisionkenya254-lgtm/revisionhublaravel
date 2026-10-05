<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ThemeList;
use App\Http\Controllers\Controller;
use App\Jobs\DefaultMailJob;
use App\Mail\DefaultMail;
use App\Models\Course;
use App\Models\User;
use App\Models\UserEducation;
use App\Models\UserExperience;
use App\Rules\CustomRecaptcha;
use App\Services\MenuCacheService;
use App\Traits\MailSenderTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Modules\Badges\app\Models\Badge;
use Modules\Blog\app\Helper\BlogHelper;
use Modules\Course\app\Helper\CourseHelper;
use Modules\Faq\app\Helper\FaqHelper;
use Modules\Frontend\app\Helper\FeaturedInstructorHelper;
use Modules\Frontend\app\Helper\SectionHelper;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\Location\app\Models\City;
use Modules\Location\app\Models\Country;
use Modules\Location\app\Models\State;
use Modules\Testimonial\app\Helper\TestimonialHelper;

class HomePageController extends Controller {
    use MailSenderTrait;

    public function index(): View {
        $theme_name = Session::has('demo_theme') ? Session::get('demo_theme') : DEFAULT_HOMEPAGE;

        $data = [
            'faqs' => collect(),
            'trendingCategories' => collect(),
            'brands' => collect(),
            'selectedInstructors' => collect(),
            'testimonials' => collect(),
            'featuredBlogs' => collect(),
        ];

        $sections = SectionHelper::getAll($theme_name);

        $data['hero'] = $sections->where('name', 'hero_section')->first();
        $data['slider'] = $sections->where('name', 'slider_section')->first();
        $data['aboutSection'] = $sections->where('name', 'about_section')->first();
        $data['newsletterSection'] = $sections->where('name', 'newsletter_section')->first();
        $data['counter'] = $sections->where('name', 'counter_section')->first();
        $data['ourFeatures'] = $sections->where('name', 'our_features_section')->first();
        $data['bannerSection'] = $sections->where('name', 'banner_section')->first();
        $data['faqSection'] = $sections->where('name', 'faq_section')->first();

        try {
            $data['faqs'] = FaqHelper::getAll();
        } catch (\Throwable $exception) {
            $data['faqs'] = collect();
        }

        try {
            $data['trendingCategories'] = collect(app(MenuCacheService::class)->getCategoryLinks(getSessionLanguage()))
                ->map(function (array $category) {
                    return (object) [
                        'slug' => $category['slug'],
                        'name' => $category['label'],
                        'translation_name' => $category['label'],
                        'icon' => '',
                        'total_courses' => 0,
                    ];
                });
        } catch (\Throwable $exception) {
            $data['trendingCategories'] = collect();
        }

        try {
            $data['brands'] = brands();
        } catch (\Throwable $exception) {
            $data['brands'] = collect();
        }

        $featuredInstructorSection = FeaturedInstructorHelper::getAll();
        $data['featuredInstructorSection'] = $featuredInstructorSection;
        $instructorIds = json_decode($featuredInstructorSection->instructor_ids ?? '[]');

        if (
            Schema::hasTable('users') &&
            Schema::hasTable('courses') &&
            Schema::hasTable('course_reviews')
        ) {
            $data['selectedInstructors'] = User::whereIn('users.id', $instructorIds)
                ->select('users.*')
                ->selectRaw('COUNT(DISTINCT courses.id) as course_count')
                ->selectRaw('COALESCE(AVG(course_reviews.rating), 0) as avg_rating')
                ->leftJoin('courses', 'users.id', '=', 'courses.instructor_id')
                ->leftJoin('course_reviews', 'courses.id', '=', 'course_reviews.course_id')
                ->groupBy('users.id')
                ->get();
        }

        try {
            $data['testimonials'] = TestimonialHelper::getAll();
        } catch (\Throwable $exception) {
            $data['testimonials'] = collect();
        }

        try {
            $data['featuredBlogs'] = BlogHelper::featuredBlogs();
        } catch (\Throwable $exception) {
            $data['featuredBlogs'] = collect();
        }
        $data['sectionSetting'] = SectionSetting();

        try {
            if (
                Schema::hasTable('courses') &&
                Schema::hasTable('course_categories') &&
                Schema::hasTable('course_reviews')
            ) {
                $featuredCourses = CourseHelper::featuredCourses($theme_name);
                $data = array_merge($data, $featuredCourses);
            }
        } catch (\Throwable $exception) {
        }

        return view('frontend.home.' . $theme_name . '.index', $data);
    }

    function countries(): JsonResponse {
        $countries = Country::where('status', 1)->get();
        return response()->json($countries);
    }

    function states(string $id): JsonResponse {
        $states = State::where(['country_id' => $id, 'status' => 1])->get();
        return response()->json($states);
    }

    function cities(string $id): JsonResponse {
        $cities = City::where(['state_id' => $id, 'status' => 1])->get();
        return response()->json($cities);
    }

    public function setCurrency() {
        $currency = allCurrencies()->where('currency_code', request('currency'))->first();
        if (session()->has('currency_code')) {
            session()->forget('currency_code');
            session()->forget('currency_position');
            session()->forget('currency_icon');
            session()->forget('currency_rate');
        }
        if ($currency) {
            session()->put('currency_code', $currency->currency_code);
            session()->put('currency_position', $currency->currency_position);
            session()->put('currency_icon', $currency->currency_icon);
            session()->put('currency_rate', $currency->currency_rate);

            $notification = __('Currency Changed Successfully');
            $notification = ['messege' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);
        }
        getSessionCurrency();
        $notification = __('Currency Changed Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function instructorDetails(string $id) {
        $instructor = User::where(['users.status' => 'active', 'users.is_banned' => 0, 'users.id' => $id])
            ->select('users.*')
            ->selectRaw('COUNT(DISTINCT CASE WHEN courses.status = \'active\' AND courses.is_approved = \'approved\' THEN courses.id END) as course_count')
            ->selectRaw('COALESCE(AVG(course_reviews.rating), 0) as avg_rating')
            ->leftJoin('courses', function ($join) {
                $join->on('users.id', '=', 'courses.instructor_id')
                    ->where('courses.status', '=', 'active')
                    ->where('courses.is_approved', '=', 'approved');
            })
            ->leftJoin('course_reviews', 'courses.id', '=', 'course_reviews.course_id')
            ->groupBy('users.id')
            ->with(['courses' => function ($query) {
                $query->with('enrollments');
            }])
            ->firstOrFail();

        $experiences = UserExperience::where(['user_id' => $id])->get();
        $educations = UserEducation::where(['user_id' => $id])->get();
        $courses = Course::query()
            ->select('courses.*')
            ->selectRaw('course_categories.*, t.name as translation_name')
            ->selectRaw('COALESCE(AVG(course_reviews.rating), 0) as avg_rating')
            ->leftJoin('course_categories', 'courses.category_id', '=', 'course_categories.id')
            ->leftJoin('course_category_translations as t', function ($q) {
                $q->on('t.course_category_id', '=', 'course_categories.id')->where('t.lang_code', getSessionLanguage());
            })
            ->leftJoin('course_reviews', 'courses.id', '=', 'course_reviews.course_id')
            ->with(['enrollments', 'instructor'])
            ->where('courses.status', 'active')
            ->where('courses.is_approved', 'approved')
            ->where('courses.instructor_id', $id)
            ->groupBy('courses.id')
            ->orderBy('courses.id', 'desc')
            ->get();
        $badges = Badge::where(['status' => 1])->get()->groupBy('key');
        return view('frontend.pages.instructor-details', compact('instructor', 'experiences', 'educations', 'courses', 'badges'));
    }

    public function allInstructors() {
        $instructors = User::where(['users.status' => 'active', 'users.is_banned' => 0, 'users.role' => 'instructor'])
            ->select('users.*')
            ->selectRaw('COUNT(DISTINCT CASE WHEN courses.status = \'active\' AND courses.is_approved = \'approved\' THEN courses.id END) as course_count')
            ->selectRaw('COALESCE(AVG(course_reviews.rating), 0) as avg_rating')
            ->leftJoin('courses', function ($join) {
                $join->on('users.id', '=', 'courses.instructor_id')
                    ->where('courses.status', '=', 'active')
                    ->where('courses.is_approved', '=', 'approved');
            })
            ->leftJoin('course_reviews', 'courses.id', '=', 'course_reviews.course_id')
            ->groupBy('users.id')
            ->having('course_count', '>', 0)
            ->orderByDesc('course_count')
            ->paginate(18);

        return view('frontend.pages.all-instructors', compact('instructors'));
    }

    function quickConnect(Request $request, string $id) {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'string', 'email', 'max:255'],
            'subject'              => ['required', 'string', 'max:255'],
            'message'              => ['required', 'string', 'max:1000'],
            'g-recaptcha-response' => Cache::get('setting')->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : 'nullable',
        ]);

        $settings = cache()->get('setting');
        $marketingSettings = cache()->get('marketing_setting');
        if ($settings->google_tagmanager_status == 'active' && $marketingSettings->instructor_contact) {
            $instructor_contact = [
                'name'    => $request->name,
                'email'   => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ];
            session()->put('instructorQuickContact', $instructor_contact);
        }

        $this->handleMailSending($validated);
        return redirect()->back()->with(['messege' => __('Message sent successfully'), 'alert-type' => 'success']);
    }

    function handleMailSending(array $mailData) {
        self::setMailConfig();

        // Get email template
        $template = EmailTemplate::where('name', 'instructor_quick_contact')->firstOrFail();

        // Prepare email content
        $message = str_replace('{{name}}', $mailData['name'], $template->message);
        $message = str_replace('{{email}}', $mailData['email'], $message);
        $message = str_replace('{{subject}}', $mailData['subject'], $message);
        $message = str_replace('{{message}}', $mailData['message'], $message);

        if (self::isQueable()) {
            DefaultMailJob::dispatch($mailData['email'], $mailData, $message);
        } else {
            Mail::to($mailData['email'])->send(new DefaultMail($mailData, $message));
        }
    }

    function changeTheme(string $theme) {
        if (Cache::get('setting')?->show_all_homepage != 1) {
            abort(404);
        }

        foreach (ThemeList::cases() as $enumTheme) {
            if ($theme == $enumTheme->value) {
                Session::put('demo_theme', $enumTheme->value);
                break;
            }
        }
        return redirect('/');
    }
}
