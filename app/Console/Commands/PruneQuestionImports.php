<?php

namespace App\Console\Commands;

use App\Services\QuestionImportService;
use Illuminate\Console\Command;

/**
 * Deletes uploaded import files that were previewed but never confirmed or cancelled.
 * Scheduled daily in routes/console.php.
 */
class PruneQuestionImports extends Command
{
    protected $signature = 'imports:prune {--hours=24 : Remove uploads older than this}';

    protected $description = 'Delete abandoned question-import uploads';

    public function handle(QuestionImportService $imports): int
    {
        $removed = $imports->prune((int) $this->option('hours'));
        $this->info("Removed {$removed} abandoned import(s).");

        return self::SUCCESS;
    }
}
