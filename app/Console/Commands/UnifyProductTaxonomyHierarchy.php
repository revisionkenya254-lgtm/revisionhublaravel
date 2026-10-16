<?php

namespace App\Console\Commands;

use App\Services\Taxonomy\TaxonomyHierarchyUnifier;
use Illuminate\Console\Command;
use Throwable;

class UnifyProductTaxonomyHierarchy extends Command
{
    protected $signature = 'taxonomy:unify-hierarchy
        {--dry-run : Report changes without writing them}
        {--json : Output the report as JSON}';

    protected $description = 'Remap references and archive non-canonical product taxonomy branches';

    public function handle(TaxonomyHierarchyUnifier $unifier): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $unifier->unify($dryRun, ! $dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Result', 'Count'], [
                ['References remapped', $report['references_remapped']],
                ['Legacy nodes archived', $report['legacy_nodes_archived']],
                ['Conflicts', count($report['conflicts'])],
            ]);

            foreach ($report['conflicts'] as $conflict) {
                $this->error($conflict);
            }
        }

        $this->info($dryRun
            ? 'Dry run complete. The database was not changed.'
            : 'Product taxonomy hierarchy unification complete.');

        return $report['conflicts'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
