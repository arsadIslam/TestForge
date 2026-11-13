<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a student may access, and until when. Granted by a paid payment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            // unique: a payment grants access exactly ONCE, even if the browser callback and the
            // webhook both report it (same idea as results.attempt_id).
            $table->foreignId('payment_id')->unique()->constrained();
            $table->foreignId('test_series_id')->nullable()->constrained('test_series'); // null = All Access
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable(); // null = lifetime
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
