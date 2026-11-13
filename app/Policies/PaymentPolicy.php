<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Only the student who started a checkout can see or continue it.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $payment->user_id === $user->id;
    }
}
