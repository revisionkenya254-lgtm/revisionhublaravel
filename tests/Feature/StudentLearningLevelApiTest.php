<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningLevelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_available_education_levels_and_student_prompt_state(): void
    {
        $levels = app(TaxonomyCatalogRepository::class)->educationLevels();

        $this->getJson('/api/bootstrap/education-levels')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.slug', $levels[0]['slug'])
            ->assertJsonPath('data.0.label', $levels[0]['label']);

        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.education_level', null)
            ->assertJsonPath('data.should_prompt_education_level', true);
    }

    public function test_student_can_update_or_dismiss_the_learning_level_prompt(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);
        $educationLevel = app(TaxonomyCatalogRepository::class)->educationLevels()[0]['slug'];

        $this->actingAs($student, 'sanctum')
            ->putJson('/api/profile/education-level', [
                'education_level' => $educationLevel,
            ])
            ->assertOk()
            ->assertJsonPath('data.education_level', $educationLevel)
            ->assertJsonPath('data.should_prompt_education_level', false);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'education_level' => $educationLevel,
        ]);

        $this->putJson('/api/profile/education-level', [
            'education_level' => 'not-a-real-level',
        ])->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['message' => ['education_level']]);

        $studentToDismiss = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->actingAs($studentToDismiss, 'sanctum')
            ->postJson('/api/profile/education-level/dismiss')
            ->assertOk()
            ->assertJsonPath('data.education_level', null)
            ->assertJsonPath('data.should_prompt_education_level', false);

        $this->assertNotNull($studentToDismiss->fresh()->education_level_prompt_dismissed_at);
    }
}
