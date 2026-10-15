<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CatalogResourceController;
use App\Http\Controllers\Frontend\InstructorProductController;
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
}
