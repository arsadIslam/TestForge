<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            // Recalculated from the pivot marks; starts at 0 for a new draft with no questions.
            $table->decimal('total_marks', 6, 2)->default(0);
            $table->decimal('pass_marks', 6, 2);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // "Published tests whose window has started" — status is the equality filter, so it goes first.
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
