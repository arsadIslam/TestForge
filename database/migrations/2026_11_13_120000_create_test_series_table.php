<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A paid group of tests, e.g. "SSC" or "WBSC". Money is stored in PAISE (integer):
 * floats can't represent 0.1 exactly, integers can.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_series', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_paise');
            $table->unsignedSmallInteger('validity_days')->nullable(); // null = lifetime access
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('tests', function (Blueprint $table) {
            // null = a free test. restrict: a series with tests can't be deleted by accident.
            $table->foreignId('test_series_id')->nullable()->after('created_by')->constrained('test_series')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('test_series_id');
        });

        Schema::dropIfExists('test_series');
    }
};
