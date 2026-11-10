<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            // restrict: a topic that still has questions cannot be deleted by accident.
            $table->foreignId('topic_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('type', ['single', 'multiple']);
            $table->text('body');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->decimal('marks', 5, 2)->default(1);
            $table->decimal('negative_marks', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Serves the question-bank filter (topic + difficulty) and the topic_id foreign key.
            $table->index(['topic_id', 'difficulty']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
