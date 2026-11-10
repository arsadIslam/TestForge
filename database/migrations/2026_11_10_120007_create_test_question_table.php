<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            // restrict: a question used in a test cannot be hard-deleted (it is soft-deleted instead).
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order');
            // Copied from the question when attached, so editing the question never changes old results.
            $table->decimal('marks', 5, 2);
            $table->decimal('negative_marks', 5, 2);

            $table->unique(['test_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_question');
    }
};
