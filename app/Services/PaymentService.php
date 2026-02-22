<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;

class PaymentService
{
    public function record(User $user, string $type, float $amount, string $method = 'online', ?int $referenceId = null): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'payment_type' => $type,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'method' => $method,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
