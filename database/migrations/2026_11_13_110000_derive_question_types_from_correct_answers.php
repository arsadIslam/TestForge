<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration: from now on a question's type is DERIVED from its options —
 * exactly one correct option → 'single', two or more → 'multiple' (QuestionService).
 * This brings any existing row in line with that rule. Scoring is unaffected: with one
 * correct option, exact-set matching gives the same result whatever the type says.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE questions q
            SET q.type = CASE
                WHEN (SELECT COUNT(*) FROM question_options o WHERE o.question_id = q.id AND o.is_correct = 1) > 1
                THEN 'multiple'
                ELSE 'single'
            END
            SQL);
    }

    public function down(): void
    {
        // Nothing to undo: the old types were either identical or inconsistent.
    }
};
