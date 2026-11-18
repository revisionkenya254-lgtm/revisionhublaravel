<?php

namespace App\Console\Commands;

use App\Services\Taxonomy\HigherEducationTaxonomyMigrator;
use Illuminate\Console\Command;

class MigrateHigherEducationTaxonomy extends Command
{
    protected $signature = 'taxonomy:migrate-higher-education
        {profile=cdacc : Taxonomy profile slug}
        {--apply : Persist the proposed canonical units, assignments and resource links}';

    protected $description = 'Preview or apply migration of legacy higher-education product metadata';

    public function handle(HigherEducationTaxonomyMigrator $migrator): int
    {
        $apply = (bool) $this->option('apply');
        $report = $migrator->migrate((string) $this->argument('profile'), $apply);
        $this->info($apply ? 'Migration applied.' : 'Dry run only; no records were changed.');
        $this->table(['Metric', 'Value'], collect($report)->except(['warnings'])->map(
            fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)), $value]
        )->values()->all());
        foreach ($report['warnings'] as $warning) {
            $this->warn($warning);
        }
        if (! $apply) {
            $this->line('Run again with --apply after reviewing the report.');
        }

        return self::SUCCESS;
    }
}
