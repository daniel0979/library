<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $fillable = ['name', 'price', 'duration_days', 'max_borrow_limit', 'description'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
