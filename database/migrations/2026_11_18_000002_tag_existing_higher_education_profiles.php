<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $profiles = [
        'tvet' => 'tvet',
        'certificate-courses' => 'certificate',
        'diploma-courses' => 'diploma',
        'undergraduate' => 'undergraduate',
        'professional-courses' => 'professional',
    ];

    public function up(): void
    {
        foreach ($this->profiles as $slug => $profile) {
            $rootId = DB::table('course_categories')->whereNull('parent_id')->where('slug', $slug)->value('id');
            if ($rootId) {
                $this->tagBranch((int) $rootId, $profile);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->profiles as $profile) {
            DB::table('course_categories')->where('taxonomy_profile', $profile)->update(['taxonomy_profile' => null]);
        }
    }

    private function tagBranch(int $rootId, string $profile): void
    {
        $pending = collect([$rootId]);
        while ($pending->isNotEmpty()) {
            $ids = $pending->splice(0, 250)->values();
            DB::table('course_categories')->whereIn('id', $ids)->update(['taxonomy_profile' => $profile]);
            $pending = $pending->merge(DB::table('course_categories')->whereIn('parent_id', $ids)->pluck('id'));
        }
    }
};
