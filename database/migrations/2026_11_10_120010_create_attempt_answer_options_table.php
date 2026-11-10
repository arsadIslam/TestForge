<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per selected option. A child table (not a JSON column) keeps multi-select
        // answers queryable with plain JOINs and protected by foreign keys.
        Schema::create('attempt_answer_options', function (Blueprint $table) {
            $table->foreignId('attempt_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained();

            $table->primary(['attempt_answer_id', 'question_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answer_options');
    }
};
