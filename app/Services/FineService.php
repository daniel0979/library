<?php

namespace App\Services;

use Carbon\Carbon;

class FineService
{
    public function calculate(string $dueDate, ?string $returnDate = null, float $dailyRate = 1.0): float
    {
        $due = Carbon::parse($dueDate)->startOfDay();
        $returned = $returnDate ? Carbon::parse($returnDate)->startOfDay() : now()->startOfDay();

        if ($returned->lte($due)) {
            return 0;
        }

        $daysLate = $due->diffInDays($returned);

        return $daysLate * $dailyRate;
    }
}
