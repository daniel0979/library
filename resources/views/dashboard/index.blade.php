@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
@php
    $isAuthenticated = auth()->check();
    $isAdmin = auth()->user()?->role?->name === 'admin';
@endphp
<div class="space-y-6" id="libraryApp" data-is-admin="{{ $isAdmin ? '1' : '0' }}" data-is-authenticated="{{ $isAuthenticated ? '1' : '0' }}">
    <section class="grid md:grid-cols-6 gap-3" id="summaryCards"></section>

    <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow space-y-3">
            <h2 class="font-semibold">Membership Plans</h2>
            <div id="planList" class="space-y-2"></div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow space-y-3">
            <h2 class="font-semibold">My Notifications</h2>
            <div id="notificationList" class="space-y-2 max-h-72 overflow-auto"></div>
        </div>
    </section>

    <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Books</h2>
                <div class="flex items-center gap-2">
                    @auth
                    <a href="{{ route('books.index') }}" class="view-books-btn text-sm px-3 py-1.5">View All Books</a>
                    @endauth
                    @if($isAdmin)
                    <button id="toggleBookForm" class="text-sm rounded bg-slate-900 text-white px-3 py-1.5">Add Book</button>
                    @endif
                </div>
            </div>

            @if($isAdmin)
            <form id="bookForm" enctype="multipart/form-data" class="hidden grid grid-cols-2 gap-2 mb-4 text-sm">
                <input name="isbn" placeholder="ISBN" class="border rounded px-2 py-1" required>
                <input name="title" placeholder="Title" class="border rounded px-2 py-1" required>
                <input name="author" placeholder="Author" class="border rounded px-2 py-1" required>
                <input name="category" placeholder="Category" class="border rounded px-2 py-1" required>
                <input name="total_copies" type="number" min="1" placeholder="Copies" class="border rounded px-2 py-1" required>
                <input name="shelf_location" placeholder="Shelf" class="border rounded px-2 py-1">
                <textarea name="description" placeholder="Book description" class="col-span-2 border rounded px-2 py-1 min-h-20"></textarea>
                <input name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="col-span-2 border rounded px-2 py-1">
                <button class="col-span-2 rounded bg-blue-600 text-white py-1.5">Save Book</button>
            </form>

            <form id="editBookForm" enctype="multipart/form-data" class="hidden grid grid-cols-2 gap-2 mb-4 text-sm border border-slate-200 rounded-lg p-3 bg-slate-50/60">
                <input type="hidden" id="editBookId" name="book_id">
                <div class="col-span-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Edit Book</div>
                <input id="editTitle" name="title" placeholder="Title" class="border rounded px-2 py-1" required>
                <input id="editAuthor" name="author" placeholder="Author" class="border rounded px-2 py-1" required>
                <input id="editCategory" name="category" placeholder="Category" class="border rounded px-2 py-1" required>
                <input id="editTotalCopies" name="total_copies" type="number" min="1" placeholder="Copies" class="border rounded px-2 py-1" required>
                <input id="editShelfLocation" name="shelf_location" placeholder="Shelf" class="border rounded px-2 py-1">
                <textarea id="editDescription" name="description" placeholder="Book description" class="col-span-2 border rounded px-2 py-1 min-h-20"></textarea>
                <input id="editCoverImage" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="border rounded px-2 py-1">
                <img id="editCoverPreview" src="" alt="Current cover" class="hidden h-24 w-16 object-contain rounded border border-slate-200 bg-white p-1 justify-self-end">
                <div class="col-span-2 flex items-center gap-2">
                    <button class="rounded bg-emerald-600 text-white px-3 py-1.5">Update Book</button>
                    <button type="button" id="cancelEditBook" class="rounded bg-slate-200 text-slate-700 px-3 py-1.5">Cancel</button>
                </div>
            </form>
            @endif

            <div id="bookList" class="space-y-2 max-h-96 overflow-auto"></div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow space-y-5">
            <div>
                <h2 class="font-semibold mb-2">My Borrowed Books</h2>
                <div id="borrowList" class="space-y-2 max-h-40 overflow-auto"></div>
            </div>

            <div>
                <h2 class="font-semibold mb-2">E-Books</h2>
                @if($isAdmin)
                <form id="ebookForm" enctype="multipart/form-data" class="grid grid-cols-2 gap-2 mb-3 text-sm">
                    <input name="title" placeholder="Title" class="border rounded px-2 py-1" required>
                    <input name="author" placeholder="Author" class="border rounded px-2 py-1" required>
                    <input name="rental_price" type="number" step="0.01" min="0" placeholder="Price" class="border rounded px-2 py-1" required>
                    <input name="rental_days" type="number" min="1" placeholder="Days" class="border rounded px-2 py-1" required>
                    <input name="file" type="file" accept=".pdf,.epub" class="col-span-2 border rounded px-2 py-1" required>
                    <button class="col-span-2 rounded bg-blue-600 text-white py-1.5">Upload E-Book</button>
                </form>
                @endif
                <div id="ebookList" class="space-y-2 max-h-48 overflow-auto"></div>
            </div>

            <div>
                <h2 class="font-semibold mb-2">Payments</h2>
                <div id="paymentList" class="space-y-2 max-h-40 overflow-auto"></div>
            </div>
        </div>
    </section>

    @if($isAdmin)
    <section class="bg-white p-4 rounded-lg shadow space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">Revenue Report (Admin)</h2>
            <button id="sendReminders" class="rounded bg-orange-600 text-white px-3 py-1.5 text-sm">Send Due Reminders</button>
        </div>
        <div id="revenueByType" class="grid md:grid-cols-4 gap-3"></div>
        <div id="revenueByMonth" class="text-sm text-slate-700"></div>
    </section>

    <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow space-y-3">
            <h2 class="font-semibold">Pending Membership Purchases</h2>
            <div id="pendingMembershipPaymentsList" class="space-y-2 max-h-80 overflow-auto"></div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow space-y-3">
            <h2 class="font-semibold">Active Membership Overview</h2>
            <div id="membershipOverviewList" class="space-y-2 max-h-80 overflow-auto"></div>
        </div>
    </section>
    @endif

    <div id="subscriptionCheckoutModal" class="hidden fixed inset-0 z-[95]">
        <div class="absolute inset-0 bg-slate-900/60" data-checkout-close></div>
        <div class="relative max-w-xl mx-auto mt-10 sm:mt-16 bg-white rounded-xl shadow-xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-slate-900">Complete Subscription Purchase</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-checkout-close>Close</button>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 mb-4">
                <p class="text-sm text-slate-600">Selected Plan</p>
                <p id="checkoutPlanName" class="font-semibold text-slate-900">-</p>
                <p id="checkoutPlanMeta" class="text-sm text-slate-600">-</p>
            </div>

            <div class="flex items-center gap-2 mb-3">
                <button type="button" class="checkout-tab-btn rounded px-3 py-1.5 text-sm bg-slate-900 text-white" data-checkout-tab="online">Online Wallet</button>
                <button type="button" class="checkout-tab-btn rounded px-3 py-1.5 text-sm bg-slate-200 text-slate-700" data-checkout-tab="card">Bank Card</button>
            </div>

            <form id="subscriptionCheckoutForm" class="space-y-3">
                <input type="hidden" id="checkoutPlanId">
                <input type="hidden" id="checkoutMethod" value="online">

                <div id="checkoutOnlinePanel" class="space-y-3">
                    <div>
                        <label class="block text-sm mb-1">Wallet Provider</label>
                        <select id="checkoutWalletProvider" class="w-full rounded border px-3 py-2" required>
                            <option value="">Choose provider</option>
                            <option value="paypal">PayPal</option>
                            <option value="wavepay">Wave Pay</option>
                            <option value="kbzpay">KBZ Pay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Wallet Account (Email or Phone)</label>
                        <input type="text" id="checkoutWalletAccount" class="w-full rounded border px-3 py-2" placeholder="example@mail.com or +959..." required>
                    </div>
                </div>

                <div id="checkoutCardPanel" class="hidden space-y-3">
                    <div>
                        <label class="block text-sm mb-1">Card Holder Name</label>
                        <input type="text" id="checkoutCardHolder" class="w-full rounded border px-3 py-2" placeholder="Name on card">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Card Number</label>
                        <input type="text" id="checkoutCardNumber" class="w-full rounded border px-3 py-2" placeholder="4242 4242 4242 4242">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Expiry (MM/YY)</label>
                            <input type="text" id="checkoutCardExpiry" class="w-full rounded border px-3 py-2" placeholder="12/29">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">CVV</label>
                            <input type="password" id="checkoutCardCvv" class="w-full rounded border px-3 py-2" placeholder="123">
                        </div>
                    </div>
                </div>

                <p id="checkoutMessage" class="hidden rounded px-3 py-2 text-sm"></p>

                <button type="submit" id="checkoutSubmitBtn" class="w-full rounded bg-emerald-600 text-white py-2 text-sm">Pay and Subscribe</button>
            </form>
        </div>
    </div>

    @guest
    <div id="authPromptModal" class="hidden fixed inset-0 z-[90]">
        <div class="absolute inset-0 bg-slate-900/60" data-auth-close></div>
        <div class="relative max-w-2xl mx-auto mt-10 sm:mt-16 bg-white rounded-xl shadow-xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Login or Create Account</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-auth-close>Close</button>
            </div>
            <p class="text-sm text-slate-600 mb-4">You can browse everything in guest mode. To borrow, reserve, rent, or subscribe, please login to your account. If you do not have an account, please register first.</p>
            <div class="flex items-center gap-2 mb-4">
                <button type="button" class="auth-tab-btn rounded px-3 py-1.5 text-sm bg-slate-900 text-white" data-auth-tab="login">Login</button>
                <button type="button" class="auth-tab-btn rounded px-3 py-1.5 text-sm bg-slate-200 text-slate-700" data-auth-tab="register">Register</button>
            </div>

            <div id="authLoginPanel">
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

            <div id="authRegisterPanel" class="hidden">
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
</div>
@endsection
