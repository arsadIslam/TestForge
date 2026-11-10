<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            // unique: one result per attempt, even if the EvaluateAttempt job runs twice.
            $table->foreignId('attempt_id')->unique()->constrained();
            // test_id and user_id are denormalised from attempts so the leaderboard reads one table.
            $table->foreignId('test_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('score', 7, 2); // signed: negative marking can push a score below zero
            $table->unsignedSmallInteger('correct_count');
            $table->unsignedSmallInteger('wrong_count');
            $table->unsignedSmallInteger('skipped_count');
            $table->decimal('accuracy', 5, 2);
            $table->unsignedInteger('time_taken_seconds');
            $table->boolean('is_passed');
            $table->dateTime('evaluated_at');
            $table->timestamps();

            // Leaderboard: WHERE test_id = ? ORDER BY score. Also serves the test_id foreign key.
            $table->index(['test_id', 'score']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
