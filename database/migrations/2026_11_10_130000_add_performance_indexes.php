<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 13 — indexes added after running EXPLAIN on the key queries against ~535k
 * answer rows (numbers in docs/performance.md). A new migration, not an edit of the
 * old ones: once a migration may have run on a server, it is never changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Leaderboard CTE reads (test_id) → user_id, score, time_taken_seconds.
            // With all four in the index, MySQL never touches the table rows ("Using index").
            $table->index(['test_id', 'user_id', 'score', 'time_taken_seconds'], 'results_leaderboard_covering_index');

            // "My results, newest first": filter + sort from one index, no filesort.
            // Added BEFORE dropping results_user_id_index — the user_id foreign key needs
            // an index starting with user_id at every moment.
            $table->index(['user_id', 'evaluated_at']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            // Toughest-questions and subject-accuracy only need these three columns of each
            // answer; covering them avoids a row lookup per answer (hundreds of thousands).
            $table->index(['attempt_id', 'question_id', 'is_correct'], 'attempt_answers_scoring_covering_index');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndex('attempt_answers_scoring_covering_index');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropIndex(['user_id', 'evaluated_at']);
            $table->dropIndex('results_leaderboard_covering_index');
        });
    }
};
