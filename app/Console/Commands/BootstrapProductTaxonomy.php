<?php

namespace App\Console\Commands;

use App\Services\Taxonomy\TaxonomyBootstrapSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class BootstrapProductTaxonomy extends Command
{
    protected $signature = 'taxonomy:bootstrap
        {--dry-run : Report changes without writing them}
        {--json : Output the report as JSON}';

    protected $description = 'Classify legacy categories and bootstrap the canonical product taxonomy';

    public function handle(TaxonomyBootstrapSynchronizer $synchronizer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $synchronizer->sync($dryRun, ! $dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Result', 'Count'], [
                ['Created', $report['created']],
                ['Updated', $report['updated']],
                ['Restored', $report['restored']],
                ['Unchanged', $report['unchanged']],
                ['Translations created', $report['translations_created']],
                ['Legacy paths preserved', count($report['legacy_paths_preserved'])],
                ['Conflicts', count($report['conflicts'])],
            ]);

            foreach ($report['legacy_paths_preserved'] as $legacyPath) {
                $this->warn(sprintf(
                    'Preserved legacy path for %s (IDs: %s).',
                    $legacyPath['slug'],
                    implode(', ', $legacyPath['ids'])
                ));
            }

            foreach ($report['conflicts'] as $conflict) {
                $this->error($conflict);
            }
        }

        if ($dryRun) {
            $this->info('Dry run complete. The database was not changed.');
        } else {
            $this->info('Product taxonomy bootstrap complete.');
        }

        return $report['conflicts'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
