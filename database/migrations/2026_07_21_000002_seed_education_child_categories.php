<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $tree = $this->educationTreeBlueprint();

            foreach ($tree as $order => $node) {
                $this->seedNode($node, null, $order + 1);
            }

            $this->clearCategoryCaches();
        });
    }

    public function down(): void
    {
        // This migration only adds child categories to the education tree.
    }

    private function seedNode(array $node, ?int $parentId, int $order = 0): ?int
    {
        $slug = (string) ($node['slug'] ?? '');
        $name = (string) ($node['name'] ?? $slug);

        if ($slug === '') {
            return null;
        }

        $existingId = DB::table('course_categories')
            ->where('slug', $slug)
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId)
            )
            ->value('id');

        if ($existingId) {
            DB::table('course_categories')
                ->where('id', $existingId)
                ->update([
                    'parent_id' => $parentId,
                    'order' => $order,
                    'status' => 1,
                    'show_at_trending' => 1,
                    'updated_at' => now(),
                ]);

            $categoryId = (int) $existingId;
        } else {
            $categoryId = (int) DB::table('course_categories')->insertGetId([
                'slug' => $slug,
                'icon' => $node['icon'] ?? null,
                'parent_id' => $parentId,
                'order' => $order,
                'show_at_trending' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hasTranslation = DB::table('course_category_translations')
            ->where('course_category_id', $categoryId)
            ->where('lang_code', 'en')
            ->exists();

        if (! $hasTranslation) {
            DB::table('course_category_translations')->insert([
                'course_category_id' => $categoryId,
                'lang_code' => 'en',
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('course_category_translations')
                ->where('course_category_id', $categoryId)
                ->where('lang_code', 'en')
                ->update([
                    'name' => $name,
                    'updated_at' => now(),
                ]);
        }

        foreach (($node['children'] ?? []) as $index => $child) {
            $this->seedNode($child, $categoryId, $index + 1);
        }

        return $categoryId;
    }

    private function educationTreeBlueprint(): array
    {
        $lowerPrimarySubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'environmental-activities', 'name' => 'Environmental Activities'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ];

        $upperPrimarySubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'science-and-technology', 'name' => 'Science & Technology'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ];

        $juniorSchoolSubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'integrated-science', 'name' => 'Integrated Science'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'pre-technical-studies', 'name' => 'Pre-Technical Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'cre-ire-hre', 'name' => 'CRE/IRE/HRE'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
        ];

        $highSchoolSubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'biology', 'name' => 'Biology'],
            ['slug' => 'chemistry', 'name' => 'Chemistry'],
            ['slug' => 'physics', 'name' => 'Physics'],
            ['slug' => 'geography', 'name' => 'Geography'],
            ['slug' => 'history', 'name' => 'History'],
            ['slug' => 'cre', 'name' => 'CRE'],
            ['slug' => 'ire', 'name' => 'IRE'],
            ['slug' => 'hre', 'name' => 'HRE'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'french', 'name' => 'French'],
            ['slug' => 'german', 'name' => 'German'],
            ['slug' => 'music', 'name' => 'Music'],
            ['slug' => 'home-science', 'name' => 'Home Science'],
        ];

        return [
            [
                'slug' => 'pre-primary',
                'name' => 'Pre-Primary',
                'children' => [
                    ['slug' => 'pp1', 'name' => 'PP1'],
                    ['slug' => 'pp2', 'name' => 'PP2'],
                ],
            ],
            [
                'slug' => 'lower-primary',
                'name' => 'Lower Primary',
                'children' => $this->gradeBranch([
                    ['slug' => 'grade-1', 'name' => 'Grade 1'],
                    ['slug' => 'grade-2', 'name' => 'Grade 2'],
                    ['slug' => 'grade-3', 'name' => 'Grade 3'],
                ], $lowerPrimarySubjects),
            ],
            [
                'slug' => 'upper-primary',
                'name' => 'Upper Primary',
                'children' => $this->gradeBranch([
                    ['slug' => 'grade-4', 'name' => 'Grade 4'],
                    ['slug' => 'grade-5', 'name' => 'Grade 5'],
                    ['slug' => 'grade-6', 'name' => 'Grade 6'],
                ], $upperPrimarySubjects),
            ],
            [
                'slug' => 'junior-school',
                'name' => 'Junior School',
                'children' => $this->gradeBranch([
                    ['slug' => 'grade-7', 'name' => 'Grade 7'],
                    ['slug' => 'grade-8', 'name' => 'Grade 8'],
                    ['slug' => 'grade-9', 'name' => 'Grade 9'],
                ], $juniorSchoolSubjects),
            ],
            [
                'slug' => 'senior-school-cbc',
                'name' => 'Senior School (CBC)',
                'children' => $this->pathwayBranch([
                    ['slug' => 'stem', 'name' => 'STEM'],
                    ['slug' => 'social-sciences', 'name' => 'Social Sciences'],
                    ['slug' => 'arts-and-sports', 'name' => 'Arts & Sports'],
                    ['slug' => 'languages', 'name' => 'Languages'],
                ]),
            ],
            [
                'slug' => 'high-school',
                'name' => 'High School',
                'children' => $this->gradeBranch([
                    ['slug' => 'form-3', 'name' => 'Form 3'],
                    ['slug' => 'form-4', 'name' => 'Form 4'],
                ], $highSchoolSubjects),
            ],
            [
                'slug' => 'tvet',
                'name' => 'TVET',
                'children' => $this->leafBranch([
                    ['slug' => 'ict', 'name' => 'ICT'],
                    ['slug' => 'electrical-engineering', 'name' => 'Electrical Engineering'],
                    ['slug' => 'mechanical-engineering', 'name' => 'Mechanical Engineering'],
                    ['slug' => 'building-technology', 'name' => 'Building Technology'],
                    ['slug' => 'plumbing', 'name' => 'Plumbing'],
                    ['slug' => 'automotive', 'name' => 'Automotive'],
                    ['slug' => 'hospitality', 'name' => 'Hospitality'],
                    ['slug' => 'agriculture', 'name' => 'Agriculture'],
                    ['slug' => 'fashion-design', 'name' => 'Fashion & Design'],
                    ['slug' => 'hair-and-beauty', 'name' => 'Hair & Beauty'],
                ]),
            ],
            [
                'slug' => 'certificate-courses',
                'name' => 'Certificate Courses',
                'children' => $this->leafBranch([
                    ['slug' => 'ict', 'name' => 'ICT'],
                    ['slug' => 'business', 'name' => 'Business'],
                    ['slug' => 'accounting', 'name' => 'Accounting'],
                    ['slug' => 'human-resource', 'name' => 'Human Resource'],
                    ['slug' => 'procurement', 'name' => 'Procurement'],
                    ['slug' => 'early-childhood-education', 'name' => 'Early Childhood Education'],
                ]),
            ],
            [
                'slug' => 'diploma-courses',
                'name' => 'Diploma Courses',
                'children' => $this->leafBranch([
                    ['slug' => 'ict', 'name' => 'ICT'],
                    ['slug' => 'business-administration', 'name' => 'Business Administration'],
                    ['slug' => 'computer-science', 'name' => 'Computer Science'],
                    ['slug' => 'nursing', 'name' => 'Nursing'],
                    ['slug' => 'education', 'name' => 'Education'],
                    ['slug' => 'engineering', 'name' => 'Engineering'],
                    ['slug' => 'procurement', 'name' => 'Procurement'],
                    ['slug' => 'hr', 'name' => 'HR'],
                    ['slug' => 'agriculture', 'name' => 'Agriculture'],
                ]),
            ],
            [
                'slug' => 'undergraduate',
                'name' => 'Undergraduate',
                'children' => $this->leafBranch([
                    ['slug' => 'school-of-computing', 'name' => 'School of Computing'],
                    ['slug' => 'school-of-business', 'name' => 'School of Business'],
                    ['slug' => 'school-of-engineering', 'name' => 'School of Engineering'],
                    ['slug' => 'school-of-education', 'name' => 'School of Education'],
                    ['slug' => 'school-of-medicine', 'name' => 'School of Medicine'],
                    ['slug' => 'school-of-law', 'name' => 'School of Law'],
                    ['slug' => 'school-of-agriculture', 'name' => 'School of Agriculture'],
                    ['slug' => 'school-of-arts', 'name' => 'School of Arts'],
                ]),
            ],
            [
                'slug' => 'professional-courses',
                'name' => 'Professional Courses',
                'children' => $this->leafBranch([
                    ['slug' => 'icdl', 'name' => 'ICDL'],
                    ['slug' => 'cisco', 'name' => 'CISCO'],
                    ['slug' => 'aws', 'name' => 'AWS'],
                    ['slug' => 'microsoft', 'name' => 'Microsoft'],
                    ['slug' => 'google-certifications', 'name' => 'Google Certifications'],
                ]),
            ],
            [
                'slug' => 'teacher-resources',
                'name' => 'Teacher Resources',
                'children' => $this->leafBranch([
                    ['slug' => 'lesson-plans', 'name' => 'Lesson Plans'],
                    ['slug' => 'schemes-of-work', 'name' => 'Schemes of Work'],
                    ['slug' => 'cbc-assessments', 'name' => 'CBC Assessments'],
                    ['slug' => 'teaching-notes', 'name' => 'Teaching Notes'],
                    ['slug' => 'marking-schemes', 'name' => 'Marking Schemes'],
                ]),
            ],
        ];
    }

    private function gradeBranch(array $grades, array $subjects): array
    {
        return array_map(function (array $grade) use ($subjects) {
            return $grade + [
                'children' => $this->subjectBranch($subjects),
            ];
        }, $grades);
    }

    private function pathwayBranch(array $pathways): array
    {
        $grades = [
            ['slug' => 'grade-10', 'name' => 'Grade 10'],
            ['slug' => 'grade-11', 'name' => 'Grade 11'],
            ['slug' => 'grade-12', 'name' => 'Grade 12'],
        ];

        return array_map(function (array $pathway) use ($grades) {
            return $pathway + [
                'children' => $this->leafBranch($grades),
            ];
        }, $pathways);
    }

    private function subjectBranch(array $subjects): array
    {
        return array_map(fn (array $subject) => $subject + ['children' => []], $subjects);
    }

    private function leafBranch(array $nodes): array
    {
        return array_map(fn (array $node) => $node + ['children' => []], $nodes);
    }

    private function clearCategoryCaches(): void
    {
        $languages = DB::table('languages')->pluck('code')->all();

        foreach ($languages as $language) {
            Cache::forget("course_categories_{$language}");
            Cache::forget("course_category_tree_{$language}");
            Cache::forget("trending_categories_{$language}");
            Cache::forget("trending_categories_with_counts_{$language}");
        }
    }
};
