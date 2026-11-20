<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Str;

class EducationFamilyService
{
    public const LOWER = 'lower_education';
    public const UPPER = 'upper_education';
    public const SUPPORT = 'supporting_resources';

    public function normalize(string $family): ?string
    {
        $family = Str::of($family)->lower()->replace('-', '_')->toString();

        return match ($family) {
            'lower', 'school', 'school_education', self::LOWER => self::LOWER,
            'upper', 'higher', 'higher_education', self::UPPER => self::UPPER,
            'support', 'resources', self::SUPPORT => self::SUPPORT,
            default => null,
        };
    }

    public function describe(array $level): array
    {
        $slug = Str::slug((string) ($level['slug'] ?? ''));
        $profile = $this->profile($level);
        $lowerSlugs = array_keys(config('product_taxonomy.class_grades_by_level', []));

        $family = $profile !== null
            ? self::UPPER
            : (in_array($slug, $lowerSlugs, true) ? self::LOWER : self::SUPPORT);

        return [
            'family' => $family,
            'family_label' => $this->label($family),
            'profile' => $profile,
            'architecture' => $profile !== null
                ? $this->upperArchitecture($profile)
                : ($family === self::LOWER ? 'grade_subject' : 'resource_tree'),
        ];
    }

    public function group(array $levels): array
    {
        $grouped = collect($levels)->groupBy(fn (array $level) => $level['family'] ?? $this->describe($level)['family']);

        return collect([self::LOWER, self::UPPER, self::SUPPORT])
            ->mapWithKeys(fn (string $family) => [$family => [
                'key' => $family,
                'label' => $this->label($family),
                'levels' => $grouped->get($family, collect())->values()->all(),
            ]])
            ->all();
    }

    public function label(string $family): string
    {
        return match ($family) {
            self::LOWER => 'Lower Education',
            self::UPPER => 'Upper Education',
            default => 'Supporting Resources',
        };
    }

    public function profile(array $level): ?string
    {
        $explicit = trim((string) ($level['taxonomy_profile'] ?? $level['profile'] ?? ''));
        if ($explicit !== '' && array_key_exists($explicit, config('higher_education_taxonomy.profiles', []))) {
            return $explicit;
        }

        $slug = Str::slug((string) ($level['slug'] ?? ''));
        $aliases = [
            'certificate-courses' => 'certificate',
            'diploma-courses' => 'diploma',
            'professional-courses' => 'professional',
            'university' => 'undergraduate',
        ];
        $candidate = $aliases[$slug] ?? $slug;

        return array_key_exists($candidate, config('higher_education_taxonomy.profiles', []))
            ? $candidate
            : null;
    }

    public function upperArchitecture(string $profile): string
    {
        return match ($profile) {
            'cdacc' => 'department_programme_level_unit_group_unit',
            'certificate', 'diploma' => 'institution_school_programme_module_unit_group_unit',
            'undergraduate' => 'university_faculty_degree_year_semester_unit_group_unit',
            'professional' => 'professional_body_qualification_stage_section_paper',
            'tvet' => 'awarding_body_trade_area_programme_module_unit_group_unit',
            default => 'higher_education_taxonomy',
        };
    }
}
