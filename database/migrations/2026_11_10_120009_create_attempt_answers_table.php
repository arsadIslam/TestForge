<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained();
            $table->boolean('is_correct')->nullable(); // NULL until the attempt is scored
            $table->decimal('marks_awarded', 5, 2)->default(0); // signed: can be negative
            $table->dateTime('answered_at');
            $table->timestamps();

            // One row per question per attempt; saving an answer again is an upsert on this key.
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
