<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.index', [
            'user' => $request->user(),
            'plans' => MembershipPlan::orderBy('price')->get(),
        ]);
    }
}
