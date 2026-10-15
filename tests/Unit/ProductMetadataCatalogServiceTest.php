<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CatalogResourceController;
use App\Http\Controllers\Frontend\InstructorProductController;
use App\Services\MenuCacheService;
use App\Services\ProductMetadataCatalogService;
use ReflectionMethod;
use Tests\TestCase;

class ProductMetadataCatalogServiceTest extends TestCase
{
    public function test_kjsea_uses_the_canonical_junior_school_subjects(): void
    {
        $catalog = new ProductMetadataCatalogService();

        $node = $catalog->assessmentNode('junior-school', 'kjsea', 'KJSEA');
        $subjectLabels = array_column($node['children'], 'label');

        $this->assertSame('KJSEA', $node['label']);
        $this->assertContains('Mathematics', $subjectLabels);
        $this->assertContains('Integrated Science', $subjectLabels);
        $this->assertContains('Pre-Technical Studies', $subjectLabels);
        $this->assertNotContains('Grade 7', $subjectLabels);
        $this->assertNotContains('Grade 8', $subjectLabels);
        $this->assertNotContains('Grade 9', $subjectLabels);
    }

    public function test_assessment_nodes_share_their_level_subject_source(): void
    {
        $catalog = new ProductMetadataCatalogService();

        foreach ([
            ['lower-primary', 'kplea', 'KPLEA'],
            ['upper-primary', 'kpsea', 'KPSEA'],
            ['junior-school', 'kjsea', 'KJSEA'],
        ] as [$level, $slug, $label]) {
            $node = $catalog->assessmentNode($level, $slug, $label);

            $this->assertSame(
                array_column($catalog->schoolSubjectsForLevel($level), 'name'),
                array_column($node['children'], 'label')
            );
        }
    }

    public function test_admin_and_instructor_past_paper_trees_expose_kjsea_subjects(): void
    {
        foreach ([CatalogResourceController::class, InstructorProductController::class] as $controllerClass) {
            $controller = $this->app->make($controllerClass);
            $method = new ReflectionMethod($controller, 'buildPaperEducationTreeForForm');
            $tree = $method->invoke($controller, collect());

            $juniorSchool = collect($tree)->firstWhere('slug', 'junior-school');
            $kjsea = collect($juniorSchool['children'] ?? [])->firstWhere('slug', 'kjsea');
            $subjectLabels = array_column($kjsea['children'] ?? [], 'label');

            $this->assertContains('Mathematics', $subjectLabels, $controllerClass);
            $this->assertContains('Integrated Science', $subjectLabels, $controllerClass);
            $this->assertNotContains('Grade 9', $subjectLabels, $controllerClass);
        }
    }

    public function test_all_product_selection_options_share_junior_school_subjects(): void
    {
        $catalog = new ProductMetadataCatalogService();
        $expectedSubjects = $catalog->subjectNamesForLevel('junior-school');

        foreach ([$catalog->paperSelectionOptions(), $catalog->quizSelectionOptions()] as $options) {
            $this->assertSame($expectedSubjects, $options['subjects_by_education_level']['Junior School']);
            $this->assertContains('KJSEA', $options['class_grades_by_level']['Junior School']);
        }
    }

    public function test_course_category_trees_receive_the_same_kjsea_branch(): void
    {
        $catalog = new ProductMetadataCatalogService();
        $tree = $catalog->addAssessmentNodes([
            ['id' => 1, 'slug' => 'junior-school', 'label' => 'Junior School', 'children' => []],
        ]);

        $kjsea = collect($tree[0]['children'])->firstWhere('slug', 'kjsea');

        $this->assertSame(
            $catalog->subjectNamesForLevel('junior-school'),
            array_column($kjsea['children'], 'label')
        );
    }

    public function test_kcbe_follows_grade_twelve_with_shared_senior_school_subjects(): void
    {
        $catalog = new ProductMetadataCatalogService();
        $nodes = $catalog->seniorSchoolClassGradeNodes();

        $this->assertSame(
            ['Grade 10', 'Grade 11', 'Grade 12', 'KCBE – National Assessment'],
            array_column($nodes, 'label')
        );
        $this->assertSame(
            $catalog->subjectNamesForLevel('senior-school-cbc'),
            array_column($nodes[3]['children'], 'label')
        );

        foreach ([CatalogResourceController::class, InstructorProductController::class] as $controllerClass) {
            $controller = $this->app->make($controllerClass);
            $method = new ReflectionMethod($controller, 'buildPaperEducationTreeForForm');
            $tree = $method->invoke($controller, collect());
            $seniorSchool = collect($tree)->firstWhere('slug', 'senior-school-cbc');

            $this->assertSame(
                ['Grade 10', 'Grade 11', 'Grade 12', 'KCBE – National Assessment'],
                array_column($seniorSchool['children'] ?? [], 'label'),
                $controllerClass
            );
        }
    }

    public function test_api_menu_keeps_senior_pathways_beneath_grades(): void
    {
        $menu = new class extends MenuCacheService {
            public function mergeWithCanonicalHierarchy(array $overlay): array
            {
                $canonical = $this->categoryMenuBlueprint();

                return $this->enforceCanonicalEducationHierarchy(
                    $this->mergeCategoryTrees($canonical, $overlay),
                    $canonical
                );
            }
        };

        $tree = $menu->mergeWithCanonicalHierarchy([[
            'slug' => 'senior-school-cbc',
            'name' => 'Senior School (CBC)',
            'children' => [
                ['slug' => 'stem', 'name' => 'STEM', 'children' => [
                    ['slug' => 'grade-10', 'name' => 'Grade 10', 'children' => []],
                ]],
                ['slug' => 'social-sciences', 'name' => 'Social Sciences', 'children' => []],
                ['slug' => 'arts-and-sports', 'name' => 'Arts & Sports', 'children' => []],
            ],
        ]]);

        $seniorSchool = collect($tree)->firstWhere('slug', 'senior-school-cbc');

        $this->assertSame(
            ['Grade 10', 'Grade 11', 'Grade 12', 'KCBE – National Assessment'],
            array_column($seniorSchool['children'], 'name')
        );
        $this->assertSame(
            ['STEM', 'Social Sciences', 'Arts & Sports', 'Languages'],
            array_column($seniorSchool['children'][0]['children'], 'name')
        );
    }
}
