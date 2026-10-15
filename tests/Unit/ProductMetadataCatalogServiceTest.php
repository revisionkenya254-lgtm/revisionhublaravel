<?php

namespace Tests\Unit;

use App\Services\ProductMetadataCatalogService;
use PHPUnit\Framework\TestCase;

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
}
