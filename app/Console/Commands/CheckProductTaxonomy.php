<?php

namespace App\Console\Commands;

use App\Services\Taxonomy\TaxonomyConsistencyService;
use Illuminate\Console\Command;
use Throwable;

class CheckProductTaxonomy extends Command
{
    protected $signature = 'taxonomy:check
        {--repair : Repair uniform shared-subject drift}
        {--json : Output the complete report as JSON}';

    protected $description = 'Check the active taxonomy hierarchy and optionally repair shared subject drift';

    public function handle(TaxonomyConsistencyService $consistency): int
    {
        try {
            $report = $consistency->inspect((bool) $this->option('repair'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Metric', 'Count'], [
                ['Issues before', count($report['issues_before'])],
                ['Repairs applied', count($report['repairs'])],
                ['Issues after', count($report['issues_after'])],
            ]);

            foreach ($report['issues_after'] as $issue) {
                $this->warn($issue['message']);
            }
        }

        $this->line($report['healthy'] ? '<info>Taxonomy is healthy.</info>' : '<error>Taxonomy requires attention.</error>');

        return $report['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
