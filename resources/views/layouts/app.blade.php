<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AetherShelf Library' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-900 page-shell">
    <header class="topbar relative z-50" data-reveal>
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <x-library-logo size="md" />
            <div class="flex items-center gap-3">
                <div class="relative z-[70]" id="currencySwitcher">
                    <button type="button" id="currencyButton" aria-expanded="false" class="currency-trigger">
                        <span id="currencyFlag" class="text-lg leading-none">&#x1F1FA;&#x1F1F8;</span>
                        <span class="text-left">
                            <span id="currencyCode" class="block text-sm font-semibold text-slate-900">USD</span>
                            <span id="currencyRateHint" class="block text-[11px] text-slate-500">Base USD</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                    </button>

                    <div id="currencyPanel" class="hidden absolute right-0 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl z-[80]">
                        <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Currency</p>
                        <div id="currencyOptions" class="space-y-1"></div>
                        <p id="currencyStatus" class="px-2 pt-2 text-[11px] text-slate-500"></p>
                    </div>
                </div>

                @auth
                    @php
                        $user = auth()->user();
                        $nameParts = preg_split('/\s+/', trim($user->name ?? 'U'));
                        $initials = strtoupper(substr(($nameParts[0] ?? 'U'), 0, 1).substr(($nameParts[1] ?? ''), 0, 1));
                    @endphp
                    <div class="relative z-[70]" id="profileMenuRoot">
                        <button type="button" id="profileMenuButton" aria-expanded="false" class="profile-trigger">
                            <span class="h-9 w-9 rounded-full bg-slate-900 text-white text-xs font-semibold flex items-center justify-center">{{ $initials }}</span>
                            <span class="text-left hidden sm:block">
                                <span class="block text-sm font-medium text-slate-900">{{ $user->name }}</span>
                                <span class="block text-xs text-slate-500">{{ ucfirst($user->role?->name ?? 'member') }}</span>
                            </span>
                            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                        </button>

                        <div id="profileMenuPanel" class="hidden absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl z-[80]">
                            <a href="{{ route('profile.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">My Profile</a>
                            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Dashboard</a>
                            <a href="{{ route('books.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">All Books</a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Logout</button>
                            </form>
                        </div>
                    </div>
                @endauth
                @guest
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Login</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Register</a>
                    </div>
                @endguest
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6 grid gap-6 lg:grid-cols-[250px,1fr]">
        <aside class="sidebar-panel h-fit sticky top-5" data-reveal data-reveal-delay="80">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Navigation</p>
            <nav class="space-y-1.5">
                <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('books.index') }}" class="side-link {{ request()->routeIs('books.index') || request()->routeIs('books.show') ? 'is-active' : '' }}">
                    <span>All Books</span>
                </a>
                @auth
                    <a href="{{ route('profile.index') }}" class="side-link {{ request()->routeIs('profile.index') ? 'is-active' : '' }}">
                        <span>Profile</span>
                    </a>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="side-link {{ request()->routeIs('login') ? 'is-active' : '' }}">
                        <span>Login</span>
                    </a>
                    <a href="{{ route('register') }}" class="side-link {{ request()->routeIs('register') ? 'is-active' : '' }}">
                        <span>Register</span>
                    </a>
                @endguest
            </nav>
        </aside>

        <main data-reveal data-reveal-delay="120">
            @yield('content')
        </main>
    </div>

    <footer class="mt-8 border-t border-slate-200/70 bg-gradient-to-br from-white to-slate-100/80" data-reveal data-reveal-delay="220">
        <div class="max-w-7xl mx-auto px-4 py-8 grid gap-6 md:grid-cols-3">
            <div class="space-y-3">
                <x-library-logo size="sm" />
                <p class="text-sm text-slate-600">
                    A modern library platform for books, memberships, digital rentals, and revenue analytics.
                </p>
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Quick Links</p>
                <div class="space-y-1 text-sm text-slate-600">
                    <p><a href="{{ route('dashboard') }}" class="hover:text-slate-900 transition-colors">Dashboard</a></p>
                    <p><a href="#" class="hover:text-slate-900 transition-colors">Membership Plans</a></p>
                    <p><a href="#" class="hover:text-slate-900 transition-colors">Digital E-Books</a></p>
                    <p><a href="#" class="hover:text-slate-900 transition-colors">Revenue Reports</a></p>
                </div>
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Connect With Us</p>
                <div class="flex items-center gap-3">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 8.5V6.8c0-.8.5-1.3 1.3-1.3H16V3h-2.1c-2.2 0-3.4 1.3-3.4 3.4v2.1H8.5V11h2v10h3V11h2.1l.4-2.5h-2.5Z"/></svg>
                </a>
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4Zm0 2a2 2 0 0 0-2 2v10c0 1.1.9 2 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H7Zm5 3.5A5.5 5.5 0 1 1 6.5 14 5.5 5.5 0 0 1 12 8.5Zm0 2A3.5 3.5 0 1 0 15.5 14 3.5 3.5 0 0 0 12 10.5Zm5.8-4.3a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2Z"/></svg>
                </a>
                <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="X">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M18.9 3H22l-6.8 7.8L23 21h-6.1l-4.8-6.2L6.7 21H3.6l7.3-8.3L1 3h6.2l4.3 5.7L18.9 3Zm-1.1 16h1.7L6.3 4.9H4.5L17.8 19Z"/></svg>
                </a>
                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="LinkedIn">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9h4v12H3V9Zm7 0h3.8v1.7h.1c.5-1 1.8-2 3.8-2 4.1 0 4.8 2.5 4.8 5.8V21h-4v-5.5c0-1.3 0-3-2-3s-2.3 1.5-2.3 2.9V21h-4V9Z"/></svg>
                </a>
                </div>
                <p class="mt-3 text-xs text-slate-500">support@aethershelf.example</p>
            </div>
        </div>
        <div class="border-t border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 py-3 text-xs text-slate-500 flex items-center justify-between">
                <span>&copy; {{ date('Y') }} AetherShelf Library. All rights reserved.</span>
                <span>Built for modern library operations</span>
            </div>
        </div>
    </footer>

    <div id="purchaseBot" data-is-authenticated="{{ auth()->check() ? '1' : '0' }}" class="purchase-bot-wrap">
        <div class="flex items-end gap-2 justify-end">
            <div class="purchase-bot-think rounded-2xl px-3 py-2 text-xs text-slate-700">
                <span>Do you wanna purchase? Click me</span>
                <span class="purchase-bot-dots" aria-hidden="true"><i></i><i></i><i></i></span>
            </div>
            <button type="button" id="purchaseBotLauncher" class="purchase-bot-launcher" aria-label="Open purchase robot">
                <span class="purchase-bot-halo" aria-hidden="true"></span>
                <svg viewBox="0 0 24 24" class="h-8 w-8 text-white relative z-[1]" fill="currentColor" aria-hidden="true">
                    <path d="M12 2a1 1 0 0 1 1 1v1h2.5A3.5 3.5 0 0 1 19 7.5V15a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5V7.5A3.5 3.5 0 0 1 8.5 4H11V3a1 1 0 0 1 1-1Zm-3.5 4A1.5 1.5 0 0 0 7 7.5V15a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3V7.5A1.5 1.5 0 0 0 15.5 6h-7ZM9 10a1.25 1.25 0 1 1 0 2.5A1.25 1.25 0 0 1 9 10Zm6 0a1.25 1.25 0 1 1 0 2.5A1.25 1.25 0 0 1 15 10Zm-6.5 5a1 1 0 0 1 1-1h5a1 1 0 1 1 0 2h-5a1 1 0 0 1-1-1Z"/>
                </svg>
            </button>
        </div>

        <div id="purchaseBotPanel" class="hidden absolute bottom-full right-0 mb-3 w-[22rem] max-w-[92vw] rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
            <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-sm">Subscription Assistant</p>
                    <p class="text-[11px] text-slate-300">Fast plan purchase chat</p>
                </div>
                <button type="button" id="purchaseBotClose" class="text-slate-300 hover:text-white text-sm">Close</button>
            </div>

            <div id="purchaseBotMessages" class="h-72 overflow-auto bg-slate-50 px-3 py-3 space-y-2"></div>
            <div id="purchaseBotChoices" class="border-t border-slate-200 px-3 py-3 flex flex-wrap gap-2 bg-white"></div>
        </div>
    </div>
</body>
</html>

