<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['role', 'activeSubscription.plan']);
        $latestSubscription = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return view('profile.index', [
            'user' => $user,
            'activeSubscription' => $user->activeSubscription,
            'latestSubscription' => $latestSubscription,
        ]);
    }
}
