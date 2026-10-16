<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxonomy_manager_requires_an_authenticated_admin(): void
    {
        $this->get('/admin/taxonomy')->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_open_the_taxonomy_manager(): void
    {
        $admin = Admin::create([
            'name' => 'Taxonomy Administrator',
            'email' => 'taxonomy-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/taxonomy')
            ->assertOk()
            ->assertSee('Taxonomy Management')
            ->assertSee('Shared Subjects / Pathways')
            ->assertSee('KJSEA');
    }
}
