@extends('layouts.app', ['title' => $book->title])

@section('content')
<section class="space-y-5" data-reveal>
    <a href="{{ route('books.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-900">&larr; Back to all books</a>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 md:grid-cols-[280px,1fr]">
            <div class="book-cover-frame min-h-[22rem]">
                @if($book->cover_image_url)
                    <img src="{{ $book->cover_image_url }}" alt="{{ $book->title }} cover" class="book-cover-img">
                @else
                    <div class="text-sm text-slate-500">No cover image</div>
                @endif
            </div>

            <div class="space-y-3">
                <h1 class="text-2xl font-semibold text-slate-900">{{ $book->title }}</h1>
                <p class="text-slate-600">Author: <span class="text-slate-800">{{ $book->author }}</span></p>
                <p class="text-slate-600">Category: <span class="text-slate-800">{{ $book->category?->name ?? '-' }}</span></p>
                <p class="text-slate-600">ISBN: <span class="text-slate-800">{{ $book->isbn }}</span></p>
                <p class="text-slate-600">Shelf: <span class="text-slate-800">{{ $book->shelf_location ?? '-' }}</span></p>
                <p class="text-slate-600">Availability: <span class="text-slate-800">{{ $book->available_copies }}/{{ $book->total_copies }}</span></p>
                <p class="text-slate-600">Views: <span class="text-slate-800">{{ number_format($book->view_count) }}</span></p>

                <div class="pt-2 border-t border-slate-200">
                    <h2 class="font-semibold text-slate-900 mb-2">Description</h2>
                    <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ $book->description ?: 'No description available for this book yet.' }}</p>
                </div>

                <div id="bookDetailActions" data-book-id="{{ $book->id }}" data-is-authenticated="{{ auth()->check() ? '1' : '0' }}" class="pt-2 border-t border-slate-200">
                    <h2 class="font-semibold text-slate-900 mb-2">Actions</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="borrowBookBtn" class="rounded bg-blue-600 text-white px-3 py-2 text-sm">{{ auth()->check() ? 'Borrow This Book' : 'Borrow (Login Required)' }}</button>
                        <button type="button" id="reserveBookBtn" class="rounded bg-amber-600 text-white px-3 py-2 text-sm">{{ auth()->check() ? 'Reserve This Book' : 'Reserve (Login Required)' }}</button>
                    </div>
                    <div class="mt-3 flex flex-wrap items-end gap-2">
                        <div>
                            <label for="bookPlanSelect" class="block text-xs text-slate-500 mb-1">Membership Plan</label>
                            <select id="bookPlanSelect" class="rounded border border-slate-300 px-3 py-2 text-sm min-w-52">
                                <option value="">Select a plan</option>
                            </select>
                        </div>
                        <button type="button" id="subscribeFromBookBtn" class="rounded bg-emerald-600 text-white px-3 py-2 text-sm">{{ auth()->check() ? 'Subscribe Now' : 'Subscribe (Login Required)' }}</button>
                    </div>
                    <p id="bookActionMessage" class="mt-3 hidden rounded px-3 py-2 text-sm"></p>
                </div>
            </div>
        </div>
    </article>
</section>

@guest
<div id="bookAuthPromptModal" class="hidden fixed inset-0 z-[90]">
    <div class="absolute inset-0 bg-slate-900/60" data-book-auth-close></div>
    <div class="relative max-w-2xl mx-auto mt-10 sm:mt-16 bg-white rounded-xl shadow-xl p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Please Login to Continue</h3>
            <button type="button" class="text-slate-500 hover:text-slate-700" data-book-auth-close>Close</button>
        </div>
        <p class="text-sm text-slate-600 mb-4">To borrow or reserve books, please login to your account. If you do not have an account, please register first.</p>
        <div class="flex items-center gap-2 mb-4">
            <button type="button" class="book-auth-tab-btn rounded px-3 py-1.5 text-sm bg-slate-900 text-white" data-book-auth-tab="login">Login</button>
            <button type="button" class="book-auth-tab-btn rounded px-3 py-1.5 text-sm bg-slate-200 text-slate-700" data-book-auth-tab="register">Register</button>
        </div>

        <div id="bookAuthLoginPanel">
            <form method="POST" action="{{ route('login.post') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" class="w-full rounded border px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <input type="password" name="password" class="w-full rounded border px-3 py-2" required>
                </div>
                <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white text-sm">Login</button>
            </form>
        </div>

        <div id="bookAuthRegisterPanel" class="hidden">
            <form method="POST" action="{{ route('register.post') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input type="text" name="name" class="w-full rounded border px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" class="w-full rounded border px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full rounded border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <input type="password" name="password" class="w-full rounded border px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full rounded border px-3 py-2" required>
                </div>
                <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white text-sm">Create Account</button>
            </form>
        </div>
    </div>
</div>
@endguest
@endsection
