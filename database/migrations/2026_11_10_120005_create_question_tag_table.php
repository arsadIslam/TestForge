<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_tag', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            // Composite PK: a tag can be attached to a question only once, and no surrogate id is needed.
            $table->primary(['question_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_tag');
    }
};
