<?php

namespace App\Console\Commands;

use App\Services\Exams\DoubleSelectSnapshotSync;
use App\Services\Questions\DoubleSelectQuestionMigrator;
use Illuminate\Console\Command;

class MigrateDoubleSelectQuestionsCommand extends Command
{
    protected $signature = 'questions:migrate-double-selects
                            {--dry-run : Preview changes without writing to the database}
                            {--force : Re-run migration even if a question looks already migrated}';

    protected $description = 'Migrate legacy double questions: first select A/B become headers, second select options copy to both selects';

    public function handle(DoubleSelectQuestionMigrator $migrator, DoubleSelectSnapshotSync $snapshotSync): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->warn('Dry run — no changes will be saved.');
        }

        $this->info('Migrating double-type questions...');

        $result = $migrator->migrate($dryRun, $force);

        if (! $dryRun) {
            $snapshotResult = $snapshotSync->syncAll();
            $this->info("Exam snapshots synced: {$snapshotResult['synced']}, skipped: {$snapshotResult['skipped']}.");
        }

        foreach ($result['messages'] as $message) {
            if (str_contains($message, 'failed')) {
                $this->error("  {$message}");
            } elseif (str_contains($message, 'skipped')) {
                $this->line("  {$message}");
            } else {
                $this->info("  {$message}");
            }
        }

        $this->newLine();
        $this->info("Done. Migrated: {$result['migrated']}, skipped: {$result['skipped']}, failed: {$result['failed']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
