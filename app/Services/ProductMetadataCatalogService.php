<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Course\app\Helper\CourseCategoryHelper;

class ProductMetadataCatalogService
{
    public function courseSelectionOptions(): array
    {
        $categories = CourseCategoryHelper::getAll();
        $catalog = $this->paperSelectionOptions();
        $tree = $this->buildCourseCategoryTree($categories);

        return [
            'tree' => $tree,
            'education_levels' => collect($tree)->pluck('label')->filter()->values()->all()
                ?: ($catalog['education_levels'] ?? []),
            'class_grades_by_level' => $catalog['class_grades_by_level'] ?? [],
            'subjects_by_education_level' => $catalog['subjects_by_education_level'] ?? [],
            'subjects' => $catalog['subjects'] ?? [],
            'exam_categories_by_level' => $catalog['exam_categories_by_level'] ?? [],
            'years' => $catalog['years'] ?? [],
            'defaults' => $this->sharedDefaults(),
        ];
    }

    private function buildCourseCategoryTree(Collection $categories): array
    {
        $byParent = $categories->groupBy(fn ($category) => $category->parent_id ? (string) $category->parent_id : 'root');

        $buildNode = function ($category, array $ancestors = []) use (&$buildNode, $byParent): array {
            $id = (int) $category->id;
            $children = in_array($id, $ancestors, true)
                ? []
                : collect($byParent[(string) $id] ?? [])
                    ->map(fn ($child) => $buildNode($child, [...$ancestors, $id]))
                    ->values()
                    ->all();

            return [
                'id' => $id,
                'slug' => (string) $category->slug,
                'label' => (string) $category->name,
                'children' => $children,
            ];
        };

        return collect($byParent['root'] ?? [])
            ->map(fn ($category) => $buildNode($category))
            ->values()
            ->all();
    }

    private function kcseSubjects(): array
    {
        return [
            'English',
            'Kiswahili',
            'Mathematics',
            'Biology',
            'Chemistry',
            'Physics',
            'History and Government',
            'Geography',
            'Christian Religious Education (CRE)',
            'Islamic Religious Education (IRE)',
            'Hindu Religious Education (HRE)',
            'Business Studies',
            'Agriculture',
            'Computer Studies',
            'Home Science',
            'Art and Design',
            'Music',
            'French',
            'German',
            'Arabic',
            'Chinese',
            'Kenya Sign Language',
            'Building Construction',
            'Power Mechanics',
            'Electricity',
            'Metalwork',
            'Woodwork',
            'Aviation Technology',
        ];
    }

    public function classGradesByLevel(): array
    {
        $schoolOfGrades = [
            'School of Arts',
            'School of Business',
            'School of Computing',
            'School of Education',
            'School of Engineering',
            'School of Hospitality',
            'School of Law',
            'School of Medicine',
            'School of Science',
            'School of Social Sciences',
            'School of Technology',
            'Health Sciences',
            'Science & Technology',
        ];
        $certificateSchoolOfGrades = [
            'School of ICT & Computing',
            'School of Business Studies',
            'School of Engineering',
            'School of Agriculture & Environmental Studies',
            'School of Health Sciences',
            'School of Hospitality & Tourism',
            'School of Fashion Design & Beauty',
            'School of Building & Construction',
            'School of Journalism, Media & Communication',
            'School of Education (ECDE)',
            'School of Social Sciences',
            'School of Languages',
            'School of Liberal Studies',
            'School of Creative Arts',
            'School of Transport & Logistics',
            'School of Maritime Studies',
        ];

        return [
            'default' => [
                'PP1',
                'PP2',
                'Grade 1',
                'Grade 2',
                'Grade 3',
                'Grade 4',
                'Grade 5',
                'Grade 6',
                'Grade 7',
                'Grade 8',
                'Grade 9',
                'Form 1',
                'Form 2',
                'Form 3',
                'Form 4',
                'Form 5',
            ],
            'Pre-Primary' => ['PP1', 'PP2'],
            'Lower Primary' => ['Grade 1', 'Grade 2', 'Grade 3'],
            'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
            'Senior School (CBC)' => ['Grade 10', 'Grade 11', 'Grade 12'],
            'High School' => ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5'],
            'Primary School' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
            'Junior School' => ['Grade 7', 'Grade 8', 'Grade 9'],
            'Senior School' => ['Grade 10', 'Grade 11', 'Grade 12'],
            'Certificate Courses' => $certificateSchoolOfGrades,
            'Diploma Courses' => $certificateSchoolOfGrades,
            'Undergraduate' => $certificateSchoolOfGrades,
            'Higher Education' => $schoolOfGrades,
            'TVET' => $schoolOfGrades,
            'University' => $schoolOfGrades,
        ];
    }

    public function coursesBySchoolSlug(): array
    {
        return [
            'ict-computing' => [
                ['slug' => 'information-communication-technology-ict', 'name' => 'Certificate in Information Communication Technology (ICT)'],
                ['slug' => 'computer-science', 'name' => 'Certificate in Computer Science'],
                ['slug' => 'software-development', 'name' => 'Certificate in Software Development'],
                ['slug' => 'computer-packages', 'name' => 'Certificate in Computer Packages'],
                ['slug' => 'networking', 'name' => 'Certificate in Networking'],
                ['slug' => 'cyber-security', 'name' => 'Certificate in Cyber Security'],
                ['slug' => 'web-development', 'name' => 'Certificate in Web Development'],
            ],
            'business-studies' => [
                ['slug' => 'accounting', 'name' => 'Certificate in Accounting'],
                ['slug' => 'business-management', 'name' => 'Certificate in Business Management'],
                ['slug' => 'human-resource-management', 'name' => 'Certificate in Human Resource Management'],
                ['slug' => 'procurement-supply-chain', 'name' => 'Certificate in Procurement & Supply Chain'],
                ['slug' => 'banking-finance', 'name' => 'Certificate in Banking & Finance'],
                ['slug' => 'marketing', 'name' => 'Certificate in Marketing'],
                ['slug' => 'secretarial-studies', 'name' => 'Certificate in Secretarial Studies'],
                ['slug' => 'office-administration', 'name' => 'Certificate in Office Administration'],
                ['slug' => 'entrepreneurship', 'name' => 'Certificate in Entrepreneurship'],
            ],
            'engineering' => [
                ['slug' => 'electrical-installation', 'name' => 'Electrical Installation'],
                ['slug' => 'electrical-electronics-engineering', 'name' => 'Electrical & Electronics Engineering'],
                ['slug' => 'power-engineering', 'name' => 'Power Engineering'],
                ['slug' => 'mechanical-engineering', 'name' => 'Mechanical Engineering'],
                ['slug' => 'automotive-engineering', 'name' => 'Automotive Engineering'],
                ['slug' => 'plant-engineering', 'name' => 'Plant Engineering'],
                ['slug' => 'production-engineering', 'name' => 'Production Engineering'],
                ['slug' => 'building-technology', 'name' => 'Building Technology'],
                ['slug' => 'civil-engineering', 'name' => 'Civil Engineering'],
                ['slug' => 'construction-technology', 'name' => 'Construction Technology'],
                ['slug' => 'masonry-plumbing', 'name' => 'Masonry Plumbing'],
                ['slug' => 'welding-fabrication', 'name' => 'Welding & Fabrication'],
                ['slug' => 'refrigeration-air-conditioning', 'name' => 'Refrigeration & Air Conditioning'],
                ['slug' => 'mechatronics', 'name' => 'Mechatronics'],
                ['slug' => 'water-engineering', 'name' => 'Water Engineering'],
            ],
            'agriculture-environmental-studies' => [
                ['slug' => 'general-agriculture', 'name' => 'General Agriculture'],
                ['slug' => 'agribusiness', 'name' => 'Agribusiness'],
                ['slug' => 'animal-production', 'name' => 'Animal Production'],
                ['slug' => 'dairy-technology', 'name' => 'Dairy Technology'],
                ['slug' => 'horticulture', 'name' => 'Horticulture'],
                ['slug' => 'crop-production', 'name' => 'Crop Production'],
                ['slug' => 'irrigation-technology', 'name' => 'Irrigation Technology'],
                ['slug' => 'environmental-conservation', 'name' => 'Environmental Conservation'],
            ],
            'health-sciences' => [
                ['slug' => 'community-health', 'name' => 'Community Health'],
                ['slug' => 'health-records-information', 'name' => 'Health Records & Information'],
                ['slug' => 'nutrition-dietetics', 'name' => 'Nutrition & Dietetics'],
                ['slug' => 'public-health', 'name' => 'Public Health'],
                ['slug' => 'medical-laboratory-assistant', 'name' => 'Medical Laboratory Assistant'],
                ['slug' => 'orthopaedic-trauma-technology', 'name' => 'Orthopaedic & Trauma Technology'],
                ['slug' => 'perioperative-theatre-technology', 'name' => 'Perioperative Theatre Technology'],
                ['slug' => 'emergency-medical-technician', 'name' => 'Emergency Medical Technician (EMT)'],
            ],
            'hospitality-tourism' => [
                ['slug' => 'food-production', 'name' => 'Food Production'],
                ['slug' => 'catering', 'name' => 'Catering'],
                ['slug' => 'hospitality-management', 'name' => 'Hospitality Management'],
                ['slug' => 'hotel-management', 'name' => 'Hotel Management'],
                ['slug' => 'housekeeping', 'name' => 'Housekeeping'],
                ['slug' => 'front-office-operations', 'name' => 'Front Office Operations'],
                ['slug' => 'travel-tourism', 'name' => 'Travel & Tourism'],
                ['slug' => 'tour-guiding', 'name' => 'Tour Guiding'],
            ],
            'fashion-design-beauty' => [
                ['slug' => 'fashion-design', 'name' => 'Fashion Design'],
                ['slug' => 'garment-making', 'name' => 'Garment Making'],
                ['slug' => 'tailoring', 'name' => 'Tailoring'],
                ['slug' => 'hair-dressing', 'name' => 'Hair Dressing'],
                ['slug' => 'beauty-therapy', 'name' => 'Beauty Therapy'],
                ['slug' => 'cosmetology', 'name' => 'Cosmetology'],
                ['slug' => 'barbering', 'name' => 'Barbering'],
            ],
            'building-construction' => [
                ['slug' => 'building-technology', 'name' => 'Building Technology'],
                ['slug' => 'quantity-survey-assistance', 'name' => 'Quantity Survey Assistance'],
                ['slug' => 'carpentry-joinery', 'name' => 'Carpentry & Joinery'],
                ['slug' => 'masonry', 'name' => 'Masonry'],
                ['slug' => 'painting-decoration', 'name' => 'Painting & Decoration'],
                ['slug' => 'tiling', 'name' => 'Tiling'],
                ['slug' => 'plumbing', 'name' => 'Plumbing'],
            ],
            'journalism-media-communication' => [
                ['slug' => 'journalism', 'name' => 'Journalism'],
                ['slug' => 'mass-communication', 'name' => 'Mass Communication'],
                ['slug' => 'public-relations', 'name' => 'Public Relations'],
                ['slug' => 'digital-media', 'name' => 'Digital Media'],
                ['slug' => 'photography', 'name' => 'Photography'],
                ['slug' => 'videography', 'name' => 'Videography'],
                ['slug' => 'film-production', 'name' => 'Film Production'],
            ],
            'education-ecde' => [
                ['slug' => 'ecde', 'name' => 'Early Childhood Development Education (ECDE)'],
                ['slug' => 'teacher-education', 'name' => 'Teacher Education'],
            ],
            'social-sciences' => [
                ['slug' => 'community-development', 'name' => 'Community Development'],
                ['slug' => 'social-work', 'name' => 'Social Work'],
                ['slug' => 'counselling-psychology', 'name' => 'Counselling Psychology'],
                ['slug' => 'criminology', 'name' => 'Criminology'],
                ['slug' => 'peace-conflict-studies', 'name' => 'Peace & Conflict Studies'],
            ],
            'languages' => [
                ['slug' => 'english', 'name' => 'English'],
                ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
                ['slug' => 'french', 'name' => 'French'],
                ['slug' => 'german', 'name' => 'German'],
                ['slug' => 'arabic', 'name' => 'Arabic'],
                ['slug' => 'chinese', 'name' => 'Chinese'],
            ],
            'liberal-studies' => [
                ['slug' => 'communication-skills', 'name' => 'Communication Skills'],
                ['slug' => 'life-skills', 'name' => 'Life Skills'],
                ['slug' => 'leadership', 'name' => 'Leadership'],
                ['slug' => 'ethics', 'name' => 'Ethics'],
                ['slug' => 'entrepreneurship', 'name' => 'Entrepreneurship'],
            ],
            'creative-arts' => [
                ['slug' => 'fine-art', 'name' => 'Fine Art'],
                ['slug' => 'graphic-design', 'name' => 'Graphic Design'],
                ['slug' => 'music', 'name' => 'Music'],
                ['slug' => 'theatre-arts', 'name' => 'Theatre Arts'],
                ['slug' => 'performing-arts', 'name' => 'Performing Arts'],
                ['slug' => 'interior-design', 'name' => 'Interior Design'],
            ],
            'transport-logistics' => [
                ['slug' => 'driving-instruction', 'name' => 'Driving Instruction'],
                ['slug' => 'logistics-management', 'name' => 'Logistics Management'],
                ['slug' => 'clearing-forwarding', 'name' => 'Clearing & Forwarding'],
                ['slug' => 'warehousing', 'name' => 'Warehousing'],
                ['slug' => 'fleet-management', 'name' => 'Fleet Management'],
            ],
            'maritime-studies' => [
                ['slug' => 'maritime-transport', 'name' => 'Maritime Transport'],
                ['slug' => 'port-operations', 'name' => 'Port Operations'],
                ['slug' => 'shipping-logistics', 'name' => 'Shipping & Logistics'],
                ['slug' => 'marine-engineering-basics', 'name' => 'Marine Engineering Basics'],
            ],
        ];
    }

    public function sharedDefaults(): array
    {
        return [
            'education_level' => 'Senior School (CBC)',
            'class_grade' => 'Grade 10',
            'exam_category' => 'KCSE',
            'subject' => 'Mathematics',
            'year' => (string) now()->year,
            'language' => 'English',
        ];
    }

    public function paperDefaults(): array
    {
        return array_merge($this->sharedDefaults(), [
            'paper' => 'Paper 1 & Paper 2',
            'access_type' => 'paid',
            'preview_pages' => 'No preview',
        ]);
    }

    public function quizDefaults(string $tier = 'short'): array
    {
        return array_merge($this->sharedDefaults(), [
            'tier' => $tier,
            'difficulty' => 'intermediate',
            'duration_minutes' => 30,
            'attempt_limit' => 1,
            'pass_mark' => 60,
            'topic' => $tier === 'short' ? __('Algebra') : __('Mixed Revision'),
            'tags' => $tier === 'short' ? 'KCSE, Mathematics, Algebra' : 'KCSE, Mathematics, Revision',
            'total_questions' => 20,
            'question_order' => 'random',
            'show_results' => 'immediately',
            'is_enabled' => true,
            'show_correct_answers' => true,
            'allow_review' => true,
            'shuffle_options' => true,
            'one_question_per_page' => false,
            'show_progress_bar' => true,
            'question_numbering' => 'continuous',
            'questions' => [],
        ]);
    }

    public function productCatalog(): array
    {
        return [
            'education_levels' => collect($this->educationLevelDefinitions())
                ->pluck('label')
                ->values()
                ->all(),
            'class_grades' => $this->classGradesByLevel()['default'],
            'class_grades_by_level' => $this->classGradesByLevel(),
            'exam_categories' => ['KCPE', 'KCSE', 'Mock', 'Mid-term', 'End-term'],
            'subjects' => $this->kcseSubjects(),
            'subjects_by_education_level' => $this->subjectsByEducationLevel(),
            'languages' => ['English', 'Swahili', 'Bilingual'],
            'years' => $this->availableYears(20),
            'access_types' => ['paid', 'free'],
            'preview_pages' => ['No preview', 'First 1 page', 'First 2 pages', 'First 3 pages'],
            'note_visibility' => $this->noteVisibilityOptions(),
            'exam_categories_by_level' => [
                'default' => ['Mid-term', 'End-term'],
                'Certificate Courses' => ['Cat', 'End-term', 'Semister 1', 'Semister 2', 'Semister 3'],
                'Diploma Courses' => ['Cat', 'End-term', 'Year 1', 'Year 2', 'Year 3'],
                'Undergraduate' => ['Cat', 'End-term', 'Year 1', 'Year 2', 'Year 3', 'Year 4'],
            ],
            'subject_overrides_by_class_grade_and_exam_category' => [
                'Accounting|Semister 1' => [
                    'Accounting Fundamentals',
                    'Business Mathematics',
                    'Communication Skills',
                    'Information Communication Technology (ICT)',
                ],
                'Accounting|Semister 2' => [
                    'Financial Accounting',
                    'Business Studies',
                    'Economics',
                    'Office Practice',
                ],
                'Accounting|Semister 3' => [
                    'Cost Accounting',
                    'Taxation',
                    'Auditing',
                    'Accounting Software',
                ],
            ],
        ];
    }

    public function commonSelectionOptions(): array
    {
        return Arr::only($this->productCatalog(), [
            'education_levels',
            'class_grades',
            'class_grades_by_level',
            'exam_categories',
            'subjects',
            'languages',
            'years',
        ]);
    }

    public function quizSelectionOptions(): array
    {
        return $this->commonSelectionOptions();
    }

    public function educationLevelDefinitions(): array
    {
        return [
            ['slug' => 'pre-primary', 'label' => 'Pre-Primary', 'source_slugs' => ['pre-primary']],
            ['slug' => 'lower-primary', 'label' => 'Lower Primary', 'source_slugs' => ['lower-primary']],
            ['slug' => 'upper-primary', 'label' => 'Upper Primary', 'source_slugs' => ['upper-primary']],
            ['slug' => 'junior-school', 'label' => 'Junior School', 'source_slugs' => ['junior-school']],
            ['slug' => 'senior-school-cbc', 'label' => 'Senior School (CBC)', 'source_slugs' => ['senior-school-cbc']],
            ['slug' => 'high-school', 'label' => 'High School', 'source_slugs' => ['high-school']],
            ['slug' => 'tvet', 'label' => 'TVET', 'source_slugs' => ['tvet']],
            ['slug' => 'certificate-courses', 'label' => 'Certificate Courses', 'source_slugs' => ['certificate-courses']],
            ['slug' => 'diploma-courses', 'label' => 'Diploma Courses', 'source_slugs' => ['diploma-courses']],
            ['slug' => 'undergraduate', 'label' => 'Undergraduate', 'source_slugs' => ['undergraduate']],
            ['slug' => 'professional-courses', 'label' => 'Professional Courses', 'source_slugs' => ['professional-courses']],
            ['slug' => 'teacher-resources', 'label' => 'Teacher Resources', 'source_slugs' => ['teacher-resources']],
        ];
    }

    public function subjectsByEducationLevel(): array
    {
        $schoolSubjects = $this->kcseSubjects();

        return collect($this->educationLevelDefinitions())
            ->mapWithKeys(fn (array $definition) => [$definition['label'] => $schoolSubjects])
            ->all();
    }

    public function paperSelectionOptions(): array
    {
        return $this->productCatalog();
    }

    public function noteVisibilityOptions(): array
    {
        return ['public', 'private'];
    }

    public function availableYears(int $yearsBack = 20): array
    {
        $currentYear = (int) now()->year;

        return range($currentYear, $currentYear - max(0, $yearsBack));
    }
}
