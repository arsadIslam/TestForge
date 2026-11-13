<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per checkout. What was bought is polymorphic (a test series or a plan);
 * the AMOUNT is copied here when the order is created, so a later price change never
 * affects an order that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->morphs('payable'); // payable_type ('test_series' | 'plan') + payable_id, indexed
            $table->unsignedInteger('amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->string('gateway', 20);
            $table->string('gateway_order_id', 64)->nullable()->unique();   // Razorpay order_...
            $table->string('gateway_payment_id', 64)->nullable()->unique(); // Razorpay pay_...
            $table->enum('status', ['created', 'paid', 'failed'])->default('created');
            $table->dateTime('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'paid_at']); // admin revenue report
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
