<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedTinyInteger('attempt_no');
            $table->enum('status', ['in_progress', 'submitted', 'auto_submitted', 'evaluated'])
                ->default('in_progress');
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->string('ip_address', 45)->nullable(); // 45 chars fits an IPv6 address
            $table->timestamps();

            // DB-level guard: two concurrent "start" requests can never both create attempt #2.
            $table->unique(['test_id', 'user_id', 'attempt_no']);
            // Used by the attempts:auto-submit command: WHERE status = 'in_progress' AND expires_at <= NOW().
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
