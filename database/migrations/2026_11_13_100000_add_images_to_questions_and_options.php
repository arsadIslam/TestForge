<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagrams/images on questions and on answer options.
 *
 * Paths point into the "public" disk (storage/app/public/questions/<random-name>.png).
 * option_text becomes nullable: an option may now be an image only ("which graph shows…").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('body');
        });

        Schema::table('question_options', function (Blueprint $table) {
            // change() re-declares the whole column, so the type is repeated with nullable().
            $table->text('option_text')->nullable()->change();
            $table->string('image_path')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('image_path');
            $table->text('option_text')->nullable(false)->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
