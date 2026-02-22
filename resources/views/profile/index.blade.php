@extends('layouts.app', ['title' => 'My Profile'])

@section('content')
<section class="space-y-6" data-reveal>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-reveal data-reveal-delay="90">
        <h2 class="text-lg font-semibold text-slate-900">Profile Information</h2>
        <p class="text-sm text-slate-500 mt-1">Your current account details.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Full Name</p>
                <p class="font-medium text-slate-900">{{ $user->name }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Email</p>
                <p class="font-medium text-slate-900">{{ $user->email }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Phone</p>
                <p class="font-medium text-slate-900">{{ $user->phone ?? 'Not set' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Role</p>
                <p class="font-medium text-slate-900">{{ ucfirst($user->role?->name ?? 'member') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-reveal data-reveal-delay="130">
        <h2 class="text-lg font-semibold text-slate-900">My Membership Plan</h2>
        <p class="text-sm text-slate-500 mt-1">Track your current subscription status and remaining days.</p>

        @php
            $subscription = $activeSubscription ?? $latestSubscription;
            $daysLeft = null;
            if ($subscription && $subscription->end_date) {
                $daysLeft = max(0, now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($subscription->end_date), false));
            }
        @endphp

        @if ($subscription)
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Plan</p>
                    <p class="font-medium text-slate-900">{{ $subscription->plan?->name ?? 'Unknown Plan' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Status</p>
                    <p class="font-medium text-slate-900">{{ ucfirst($subscription->status) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Start Date</p>
                    <p class="font-medium text-slate-900">{{ $subscription->start_date }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">End Date</p>
                    <p class="font-medium text-slate-900">{{ $subscription->end_date }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                    <p class="text-xs text-slate-500">Days Left</p>
                    <p class="font-medium text-slate-900">
                        @if($subscription->status === 'pending')
                            Pending admin review
                        @else
                            {{ $daysLeft ?? 0 }} day(s)
                        @endif
                    </p>
                </div>
            </div>
        @else
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                You have not subscribed to a membership plan yet.
            </div>
        @endif
    </div>
</section>
@endsection
