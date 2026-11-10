<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->timestamps();

            // Slugs only need to be unique inside a subject ("basics" can exist in Maths and Physics).
            // subject_id is the leftmost column, so this index also serves the foreign key.
            $table->unique(['subject_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
