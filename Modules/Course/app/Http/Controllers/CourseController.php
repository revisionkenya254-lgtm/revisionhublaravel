<?php

namespace Modules\Course\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CoursePartnerInstructor;
use App\Models\CourseSelectedLanguage;
use App\Models\CourseSelectedLevel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Helper\CourseCategoryHelper;
use Modules\Course\app\Http\Requests\CourseStoreRequest;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Models\CourseLanguage;
use Modules\Course\app\Models\CourseLevel;
use Modules\Order\app\Models\OrderItem;
use App\Services\Storage\CourseMediaStorageService;

class CourseController extends Controller {
    public function __construct(
        protected CourseMediaStorageService $courseMediaStorage
    ) {
    }

    function index(Request $request): View {
        $query = Course::query();
        $query->when($request->keyword, fn($q) => $q->where('title', 'like', '%' . request('keyword') . '%'));
        $query->when($request->category, function ($q) use ($request) {
            $q->whereHas('category', function ($q) use ($request) {
                $q->where('id', $request->category);
            });
        });
        $query->when($request->date && $request->filled('date'), fn($q) => $q->whereDate('created_at', $request->date));
        $query->when($request->approve_status && $request->filled('approve_status'), fn($q) => $q->where('is_approved', $request->approve_status));
        $query->when($request->status && $request->filled('status'), fn($q) => $q->where('status', $request->status));
        $query->when($request->instructor && $request->filled('instructor'), function ($q) use ($request) {
            $q->where('instructor_id', $request->instructor);
        });
        $query->withCount('enrollments');
        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $courses = $request->par_page == 'all' ?
        $query->orderBy('id', $orderBy)->get() :
        $query->orderBy('id', $orderBy)->paginate($request->par_page ?? null)->withQueryString();
        $categories = CourseCategoryHelper::getAll();
        $instructors = User::where('role', 'instructor')->get();
        return view('course::course.index', compact('courses', 'categories', 'instructors'));
    }

    function create() {
        $instructors = User::where('role', 'instructor')->get();
        $categories = CourseCategoryHelper::getTree();
        $levels = CourseLevel::with('translation')->where('status', 1)->get();
        $languages = CourseLanguage::where('status', 1)->get();

        return view('course-builder.create', compact('instructors', 'categories', 'levels', 'languages'))
            ->with('isAdmin', true);
    }

    function editView(string $id) {
        Session::put('course_create', $id);
        $course = Course::findOrFail($id);
        $instructors = User::where('role', 'instructor')->get();
        $editMode = true;
        return view('course::course.create', compact('course', 'editMode', 'instructors'));
    }

    function store(CourseStoreRequest $request) {
        if ($request->edit_mode == 1) {
            $course = Course::findOrFail($request->id);
            $course->instructor_id = $request->instructor;
            $oldThumbnail = $course->thumbnail;
            $oldVideoStorage = $course->demo_video_storage;
            $oldVideoSource = $course->demo_video_source;
        } else {
            $course = new Course();
            $slug = generateUniqueSlug(Course::class, $request->title);
            $course->slug = $slug;
            $oldThumbnail = null;
            $oldVideoStorage = null;
            $oldVideoSource = null;
        }

        if (
            $request->demo_video_storage === 'bunny_stream'
            && ! $request->hasFile('demo_video_file')
            && ! ($course->exists && $course->demo_video_storage === 'bunny_stream' && filled($course->demo_video_source))
        ) {
            throw ValidationException::withMessages([
                'demo_video_file' => __('A Bunny Stream video file is required.'),
            ]);
        }

        $course->title = $request->title;
        $course->seo_description = $request->seo_description;
        $course->price = $request->price;
        $course->discount = $request->discount_price;
        $course->description = $request->description;
        $course->instructor_id = $request->instructor;
        if ($request->boolean('builder_flow')) {
            $course->category_id = $request->category;
            $course->duration = $request->course_duration;
            $course->capacity = $request->capacity;
            $course->qna = $request->boolean('qna');
            $course->certificate = $request->boolean('certificate');
            $course->status = 'is_draft';
        }
        $course->save();

        $this->storeCourseMedia($request, $course, 'admin', (int) auth('admin')->id(), $oldThumbnail, $oldVideoStorage, $oldVideoSource);
        $course->save();

        if ($request->boolean('builder_flow')) {
            $this->syncBuilderOptions($course, $request);
            $chapter = CourseChapter::where('course_id', $course->id)->where('order', 1)->first();
            if (! $chapter) {
                $chapter = new CourseChapter();
                $chapter->course_id = $course->id;
                $chapter->instructor_id = $course->instructor_id;
                $chapter->title = __('Section 1: Introduction');
                $chapter->order = 1;
                $chapter->status = 'active';
                $chapter->save();
            }
        }

        // save course id in session
        Session::put('course_create', $course->id);

        return response()->json([
            'status'   => 'success',
            'message'  => $request->boolean('builder_flow') ? __('Course created. Add your first lesson.') : __('Updated successfully'),
            'redirect' => $request->boolean('builder_flow')
                ? route('admin.lessons.create', ['course' => $course->id, 'chapter' => $chapter->id])
                : route('admin.courses.edit', ['id' => $course->id, 'step' => $request->next_step]),
        ]);
    }

    private function syncBuilderOptions(Course $course, Request $request): void
    {
        CourseSelectedLevel::where('course_id', $course->id)->delete();
        foreach ($request->input('levels', []) as $levelId) {
            CourseSelectedLevel::create(['course_id' => $course->id, 'level_id' => $levelId]);
        }

        CourseSelectedLanguage::where('course_id', $course->id)->delete();
        foreach ($request->input('languages', []) as $languageId) {
            CourseSelectedLanguage::create(['course_id' => $course->id, 'language_id' => $languageId]);
        }
    }

    private function storeCourseMedia(Request $request, Course $course, string $actorRole, int $actorId, ?string $oldThumbnail = null, ?string $oldVideoStorage = null, ?string $oldVideoSource = null): void
    {
        if ($request->hasFile('thumbnail_file')) {
            $thumbnail = $this->courseMediaStorage->storeThumbnail(
                $request->file('thumbnail_file'),
                $actorRole,
                $actorId,
                'course',
                $course->id
            );

            $course->thumbnail = $thumbnail['url'];

            if (filled($oldThumbnail) && $oldThumbnail !== $course->thumbnail) {
                $this->courseMediaStorage->deleteThumbnail($oldThumbnail);
            }
        } elseif ($request->filled('thumbnail')) {
            $course->thumbnail = $request->thumbnail;
        }

        if ($request->hasFile('demo_video_file')) {
            $video = $this->courseMediaStorage->storeDemoVideo(
                $request->file('demo_video_file'),
                $course->title,
                $actorRole,
                $actorId,
                'course',
                $course->id
            );

            $course->demo_video_storage = $video['storage'];
            $course->demo_video_source = $video['video_id'];

            if ($oldVideoStorage === 'bunny_stream' && filled($oldVideoSource) && $oldVideoSource !== $video['video_id']) {
                $this->courseMediaStorage->deleteDemoVideo($oldVideoSource);
            }
        } elseif ($request->demo_video_storage === 'bunny_stream') {
            if ($course->demo_video_storage === 'bunny_stream' && filled($course->demo_video_source)) {
                $course->demo_video_storage = 'bunny_stream';
            } else {
                throw ValidationException::withMessages([
                    'demo_video_file' => __('A Bunny Stream video file is required.'),
                ]);
            }
        } elseif ($request->demo_video_storage === 'upload') {
            $course->demo_video_storage = 'upload';
            $course->demo_video_source = $request->upload_path;
        } else {
            $course->demo_video_storage = $request->demo_video_storage;
            $course->demo_video_source = $request->external_path;
        }
    }

    public function edit(Request $request) {
        if (!Session::get('course_create')) {
            return redirect(route('admin.courses.create'));
        }

        switch (request('step')) {
        case '1':
            $course = Course::findOrFail($request->id);
            $instructors = User::where('role', 'instructor')->get();
            $editMode = true;
            return view('course::course.create', compact('course', 'editMode', 'instructors'));
        case '2':
            $courseId = request('id');
            $categories = CourseCategoryHelper::getTree();
            $course = Course::findOrFail($request->id);
            $levels = CourseLevel::with(['translation'])->where('status', 1)->get();
            $category = CourseCategoryHelper::find($course->category_id);
            $languages = CourseLanguage::where('status', 1)->get();
            return view('course::course.more-information', compact(
                'categories',
                'courseId',
                'course',
                'levels',
                'category',
                'languages'
            ));
        case '3':
            $chapters = CourseChapter::with(['chapterItems'])->where(['course_id' => $request->id, 'status' => 'active'])->orderBy('order')->get();
            return view('course::course.course-content', compact('chapters'));
        case '4':
            $course = Course::findOrFail($request->id);

            $year = $request->input('year', Carbon::now()->year);

            // Get start and end of the year
            $start = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $end = $start->copy()->endOfYear();

            $orderItems = OrderItem::where('course_id', $course->id)->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })->selectRaw('YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(price) as total_price,
                AVG(commission_rate) as commission_rate')->whereBetween('created_at', [$start, $end])->groupBy('year', 'month')->orderBy('month', 'asc')->get();

            $monthlySales = array_fill(1, 12, 0);
            $commissionMonthly = array_fill(1, 12, 0);
            $netMonthly = array_fill(1, 12, 0);

            foreach ($orderItems as $item) {
                $totalAmount = $item->total_price;
                $commissionAmount = $totalAmount * ($item->commission_rate / 100);
                $netEarnings = $totalAmount - $commissionAmount;
                $monthlySales[$item->month] = $totalAmount;
                $commissionMonthly[$item->month] = $commissionAmount;
                $netMonthly[$item->month] = $netEarnings;
            }

            $oldestYear = Carbon::parse(OrderItem::orderBy('created_at', 'asc')->first()?->created_at)->year ?? Carbon::now()->year;
            $latestYear = Carbon::parse(OrderItem::orderBy('created_at', 'desc')->first()?->created_at)->year ?? Carbon::now()->year;
            $month_labels = array_map(fn($month) => Carbon::create()->month($month)->format('M'), array_keys($monthlySales));

            $commission_monthly_data = array_values($commissionMonthly);
            $net_monthly_data = array_values($netMonthly);
            $order_monthly_data = array_values($monthlySales);

            $total_chapter_items = $course->chapterItems()->count();
            $enrollments = $course->enrollments;
            $progress_ranges = array_fill_keys(['0-10', '10-20', '20-30', '30-40', '40-50', '50-60', '60-70', '70-80', '80-90', '90-100'], 0);

            foreach ($enrollments as $enrollment) {
                $percentage = min(($total_chapter_items > 0 ? $course->progresses()->where('user_id', $enrollment->user_id)->count() / $total_chapter_items * 100 : 0), 100);

                $range = $percentage == 100 ? '90-100' : floor($percentage / 10) * 10 . '-' . (floor($percentage / 10) * 10 + 10);
                $progress_ranges[$range]++;
            }
            $total_enrollments = $enrollments->count();

            return view('course::course.analytics', compact('course', 'progress_ranges', 'total_enrollments', 'oldestYear', 'latestYear', 'month_labels', 'commission_monthly_data', 'net_monthly_data', 'order_monthly_data'));
        case '5':
            $courseId = request('id');
            $course = Course::findOrFail($courseId);
            return view('course::course.finish', compact('course'));
        default:
            break;
        }
    }

    function update(Request $request) {
        switch ($request->step) {
        case '2':
            $request->validate([
                'course_duration' => ['required', 'numeric', 'min:0'],
                'category'        => ['required'],
            ]);
            $this->storeMoreInfo($request);
            return response()->json([
                'status'   => 'success',
                'message'  => __('Updated Successfully'),
                'redirect' => route('admin.courses.edit', ['id' => Session::get('course_create'), 'step' => $request->next_step]),
            ]);
            break;
        case '3':
            return response()->json([
                'status'   => 'success',
                'message'  => __('Updated successfully'),
                'redirect' => route('admin.courses.edit', ['id' => Session::get('course_create'), 'step' => $request->next_step]),
            ]);
            break;
        case '4':
            return response()->json([
                'status'   => 'success',
                'message'  => __('Updated successfully'),
                'redirect' => route('admin.courses.edit', ['id' => Session::get('course_create'), 'step' => $request->next_step]),
            ]);
            break;
        case '5':
            $request->validate([
                'status'               => ['required'],
                'message_for_reviewer' => ['nullable', 'max:1000'],
            ]);
            $this->storeFinish($request);
            return response()->json([
                'status'   => 'success',
                'message'  => __('Updated Successfully'),
                'redirect' => $request->next_step == 5 ? route('admin.courses.index') : route('admin.courses.edit', ['id' => Session::get('course_create'), 'step' => $request->next_step]),
            ]);

        default:
            # code...
            break;
        }
    }

    function storeMoreInfo(Request $request) {
        checkAdminHasPermissionAndThrowException('course.management');
        $course = Course::findOrFail($request->course_id);
        $course->capacity = $request->capacity;
        $course->duration = $request->course_duration;
        $course->category_id = $request->category;
        $course->qna = $request->qna;
        $course->downloadable = $request->downloadable;
        $course->certificate = $request->certificate;
        $course->partner_instructor = $request->partner_instructor;
        $course->save();

        // delete unselected partner instructor
        CoursePartnerInstructor::where('course_id', $course->id)
            ->whereNotIn('instructor_id', $request->partner_instructors ?? [])->delete();

        // insert partner instructor
        foreach ($request->partner_instructors ?? [] as $instructor) {
            CoursePartnerInstructor::updateOrCreate(
                ['course_id' => $course->id, 'instructor_id' => $instructor],
            );
        }

        // insert levels
        CourseSelectedLevel::where('course_id', $course->id)
            ->whereNotIn('level_id', $request->levels ?? [])->delete();

        foreach ($request->levels ?? [] as $level) {
            CourseSelectedLevel::updateOrCreate(
                ['course_id' => $course->id, 'level_id' => $level],
            );
        }

        //insert languages
        CourseSelectedLanguage::where('course_id', $course->id)
            ->whereNotIn('language_id', $request->languages ?? [])->delete();

        foreach ($request->languages ?? [] as $language) {
            CourseSelectedLanguage::updateOrCreate(
                ['course_id' => $course->id, 'language_id' => $language],
            );
        }
    }

    function storeFinish(Request $request) {
        checkAdminHasPermissionAndThrowException('course.management');
        $course = Course::findOrFail($request->course_id);
        $course->message_for_reviewer = $request->message_for_reviewer;
        $course->status = $request->status;
        // $course->is_approved = 'approved';
        $course->save();
    }

    function getInstructors(Request $request) {
        $instructors = User::where('role', 'instructor')
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%');
            })
            ->where('id', '!=', auth()->id())
            ->get();
        return response()->json($instructors);
    }

    function statusUpdate(Request $request, string $id) {
        $course = Course::findOrFail($id);
        $course->is_approved = $request->status;
        $course->save();
        return response(['status' => 'success', 'message' => __('Updated successfully')]);
    }

    function destroy(string $id) {
        checkAdminHasPermissionAndThrowException('course.management');
        $course = Course::findOrFail($id);
        if ($course->enrollments()->count() > 0) {
            return redirect()->back()->with(['alert-type' => 'error', 'messege' => __('The course cannot be deleted because it has enrollments.')]);
        }
        $course->delete();

        return response()->json(['status' => 'success', 'message' => __('Course deleted successfully')]);
    }
}
