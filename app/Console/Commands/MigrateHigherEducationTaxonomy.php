<?php

namespace App\Console\Commands;

use App\Services\Taxonomy\HigherEducationTaxonomyMigrator;
use Illuminate\Console\Command;

class MigrateHigherEducationTaxonomy extends Command
{
    protected $signature = 'taxonomy:migrate-higher-education
        {profile=all : Taxonomy profile slug, or all}
        {--apply : Persist the proposed canonical units, assignments and resource links}
        {--csv= : Export the dry-run inventory to a CSV file}';

    protected $description = 'Preview or apply migration of legacy higher-education product metadata';

    public function handle(HigherEducationTaxonomyMigrator $migrator): int
    {
        $apply = (bool) $this->option('apply');
        $profile = (string) $this->argument('profile');
        $profiles = $profile === 'all'
            ? array_keys(config('higher_education_taxonomy.profiles', []))
            : [$profile];
        $reports = collect($profiles)->map(fn (string $profileSlug) => $migrator->migrate($profileSlug, $apply));
        $this->info($apply ? 'Migration applied.' : 'Dry run only; no records were changed.');
        foreach ($reports as $report) {
            $this->newLine();
            $this->info(strtoupper($report['profile']));
            $this->table(['Metric', 'Value'], collect($report)->except(['warnings', 'inventory'])->map(
                fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)), $value]
            )->values()->all());
            foreach ($report['warnings'] as $warning) {
                $this->warn($warning);
            }
        }
        if ($path = $this->option('csv')) {
            $this->writeCsv((string) $path, $reports->flatMap(fn (array $report) => $report['inventory'])->all());
            $this->info('Inventory exported to '.$path);
        }
        if (! $apply) {
            $this->line('Run again with --apply after reviewing the report.');
        }

        return self::SUCCESS;
    }

    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV path: '.$path);
        }
        $headers = ['profile', 'curriculum_owner', 'programme', 'programme_slug', 'legacy_unit_name', 'proposed_unit_slug', 'product_count', 'action'];
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $header) => $row[$header] ?? '', $headers));
        }
        fclose($handle);
    }
}
