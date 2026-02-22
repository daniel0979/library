<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\Category;
use App\Models\Ebook;
use App\Models\EbookRental;
use App\Models\MembershipPlan;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Services\ExternalBookReaderService;
use App\Services\FineService;
use App\Services\NotificationFeedService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LibraryApiController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'books' => Book::count(),
            'available_books' => (int) Book::sum('available_copies'),
            'borrowed_active' => BorrowTransaction::whereIn('status', ['borrowed', 'overdue'])->count(),
            'overdue' => BorrowTransaction::where('status', 'overdue')->count(),
            'total_revenue' => (float) Payment::where('status', 'paid')->sum('amount'),
            'my_active_subscription' => $user->activeSubscription()->with('plan')->first(),
        ]);
    }

    public function books(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'availability' => ['nullable', 'in:all,available,unavailable'],
            'sort' => ['nullable', 'in:latest,title_asc,title_desc,author_asc,author_desc'],
        ]);

        $query = Book::query()->with('category:id,name');

        if (! empty($validated['q'])) {
            $term = trim($validated['q']);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('isbn', 'like', "%{$term}%");
            });
        }

        if (! empty($validated['category']) && $validated['category'] !== 'all') {
            $query->whereHas('category', function ($q) use ($validated) {
                $q->where('name', $validated['category']);
            });
        }

        if (($validated['availability'] ?? 'all') === 'available') {
            $query->where('available_copies', '>', 0);
        }

        if (($validated['availability'] ?? 'all') === 'unavailable') {
            $query->where('available_copies', '<=', 0);
        }

        $sort = $validated['sort'] ?? 'latest';
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'author_asc' => $query->orderBy('author'),
            'author_desc' => $query->orderByDesc('author'),
            default => $query->latest(),
        };

        return response()->json($query->get());
    }

    public function bookFilters()
    {
        return response()->json([
            'categories' => Category::query()
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }

    public function storeBook(Request $request, NotificationFeedService $notificationFeed)
    {
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn'],
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $category = Category::firstOrCreate(['name' => $validated['category']]);
        $coverImagePath = $request->hasFile('cover_image')
            ? $request->file('cover_image')->store('books/covers', 'public')
            : null;

        $book = Book::create([
            'isbn' => $validated['isbn'],
            'title' => $validated['title'],
            'author' => $validated['author'],
            'category_id' => $category->id,
            'total_copies' => $validated['total_copies'],
            'available_copies' => $validated['total_copies'],
            'shelf_location' => $validated['shelf_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'cover_image_path' => $coverImagePath,
        ]);

        $notificationFeed->notifyNewBook($book);

        return response()->json($book->load('category:id,name'), 201);
    }

    public function updateBook(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $category = Category::firstOrCreate(['name' => $validated['category']]);

        $borrowedCount = max(0, $book->total_copies - $book->available_copies);
        $newAvailable = max(0, $validated['total_copies'] - $borrowedCount);

        $coverImagePath = $book->cover_image_path;
        if ($request->hasFile('cover_image')) {
            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }
            $coverImagePath = $request->file('cover_image')->store('books/covers', 'public');
        }

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'category_id' => $category->id,
            'total_copies' => $validated['total_copies'],
            'available_copies' => $newAvailable,
            'shelf_location' => $validated['shelf_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'cover_image_path' => $coverImagePath,
        ]);

        return response()->json($book->load('category:id,name'));
    }

    public function deleteBook(Book $book)
    {
        if ($book->cover_image_path) {
            Storage::disk('public')->delete($book->cover_image_path);
        }

        $book->delete();

        return response()->json(['message' => 'Book deleted']);
    }

    public function plans()
    {
        return response()->json(MembershipPlan::orderBy('price')->get());
    }

    public function currencyRates()
    {
        $base = 'USD';
        $supported = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'SGD', 'INR', 'THB', 'CNY'];
        $fallback = [
            'base' => $base,
            'rates' => ['USD' => 1.0],
            'updated_at' => null,
            'fallback' => true,
            'message' => 'Live exchange rates unavailable. Showing USD only.',
        ];

        $apiKey = config('services.exchangerate_api.key');
        if (empty($apiKey)) {
            return response()->json($fallback);
        }

        try {
            $response = Http::timeout(8)->acceptJson()->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");
            if (! $response->ok()) {
                return response()->json($fallback);
            }

            $payload = $response->json();
            $rawRates = $payload['conversion_rates'] ?? null;
            if (($payload['result'] ?? null) !== 'success' || ! is_array($rawRates)) {
                return response()->json($fallback);
            }

            $rates = [];
            foreach ($supported as $code) {
                if (isset($rawRates[$code])) {
                    $rates[$code] = (float) $rawRates[$code];
                }
            }
            $rates['USD'] = 1.0;

            return response()->json([
                'base' => $base,
                'rates' => $rates,
                'updated_at' => $payload['time_last_update_utc'] ?? null,
                'fallback' => false,
            ]);
        } catch (\Throwable $e) {
            return response()->json($fallback);
        }
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:membership_plans,id'],
            'method' => ['nullable', 'in:cash,card,online'],
        ]);

        $user = $request->user();
        $plan = MembershipPlan::findOrFail($validated['plan_id']);

        $hasPendingReview = Payment::query()
            ->where('user_id', $user->id)
            ->where('payment_type', 'membership')
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingReview) {
            return response()->json([
                'message' => 'You already have a pending subscription payment. Please wait for admin review.',
            ], 422);
        }

        $payload = DB::transaction(function () use ($user, $plan, $validated) {
            $now = now();

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'start_date' => $now->toDateString(),
                'end_date' => $now->addDays($plan->duration_days)->toDateString(),
                'status' => 'pending',
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'payment_type' => 'membership',
                'reference_id' => $subscription->id,
                'amount' => (float) $plan->price,
                'method' => $validated['method'] ?? 'online',
                'status' => 'pending',
                'paid_at' => null,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'general',
                'message' => '[Membership] Payment request submitted. Please wait while admin reviews your payment.',
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            return compact('subscription', 'payment');
        });

        return response()->json([
            'status' => 'pending_review',
            'message' => 'Payment submitted. Please wait 5 minutes while admin reviews and accepts your request.',
            'subscription' => $payload['subscription']->load('plan'),
            'payment' => $payload['payment'],
        ]);
    }

    public function pendingMembershipPayments()
    {
        $rows = Payment::query()
            ->where('payment_type', 'membership')
            ->where('status', 'pending')
            ->with([
                'user:id,name,email',
                'user.activeSubscription.plan:id,name,duration_days',
            ])
            ->latest()
            ->get()
            ->map(function (Payment $payment) {
                $subscription = Subscription::with('plan:id,name,duration_days,max_borrow_limit')
                    ->find($payment->reference_id);

                $activeSub = $payment->user?->activeSubscription;
                $daysLeft = null;
                if ($activeSub && $activeSub->end_date) {
                    $daysLeft = max(0, now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSub->end_date), false));
                }

                return [
                    'payment_id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'created_at' => $payment->created_at,
                    'user' => $payment->user,
                    'requested_plan' => $subscription?->plan,
                    'current_active_plan' => $activeSub?->plan,
                    'current_plan_days_left' => $daysLeft,
                ];
            });

        return response()->json($rows);
    }

    public function reviewMembershipPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($payment->payment_type !== 'membership' || $payment->status !== 'pending') {
            return response()->json(['message' => 'Only pending membership payments can be reviewed.'], 422);
        }

        $subscription = Subscription::with(['user', 'plan'])->find($payment->reference_id);
        if (! $subscription) {
            return response()->json(['message' => 'Subscription record not found for this payment.'], 404);
        }

        $result = DB::transaction(function () use ($validated, $payment, $subscription) {
            if ($validated['action'] === 'approve') {
                Subscription::where('user_id', $subscription->user_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $subscription->id)
                    ->update(['status' => 'expired']);

                $subscription->update([
                    'status' => 'active',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays($subscription->plan->duration_days)->toDateString(),
                ]);

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                Notification::create([
                    'user_id' => $subscription->user_id,
                    'type' => 'general',
                    'message' => '[Membership] Admin has accepted your payment. Thank you for your purchase.',
                    'sent_at' => now(),
                    'status' => 'sent',
                ]);

                return ['status' => 'approved'];
            }

            $subscription->update(['status' => 'cancelled']);
            $payment->update([
                'status' => 'failed',
                'paid_at' => null,
            ]);

            $message = '[Membership] Admin rejected your payment request.';
            if (! empty($validated['note'])) {
                $message .= ' Note: '.$validated['note'];
            }

            Notification::create([
                'user_id' => $subscription->user_id,
                'type' => 'general',
                'message' => $message,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            return ['status' => 'rejected'];
        });

        return response()->json([
            'message' => 'Membership payment review completed.',
            'result' => $result['status'],
        ]);
    }

    public function membershipOverview()
    {
        $rows = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name,duration_days,max_borrow_limit'])
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function (Subscription $subscription) {
                $daysLeft = max(0, now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($subscription->end_date), false));

                return [
                    'subscription_id' => $subscription->id,
                    'user' => $subscription->user,
                    'plan' => $subscription->plan,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'days_left' => $daysLeft,
                ];
            });

        return response()->json($rows);
    }

    public function borrow(Request $request)
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
        ]);

        $user = $request->user()->load('activeSubscription.plan');

        if (! $user->activeSubscription) {
            return response()->json(['message' => 'Active membership required'], 403);
        }

        $activeBorrowCount = BorrowTransaction::where('user_id', $user->id)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->count();

        if ($activeBorrowCount >= $user->activeSubscription->plan->max_borrow_limit) {
            return response()->json(['message' => 'Borrow limit reached for your plan'], 422);
        }

        $book = Book::findOrFail($validated['book_id']);

        if ($book->available_copies < 1) {
            return response()->json(['message' => 'Book is not available'], 422);
        }

        $borrow = DB::transaction(function () use ($book, $user) {
            $book->decrement('available_copies');

            return BorrowTransaction::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'borrowed',
                'fine_amount' => 0,
            ]);
        });

        return response()->json($borrow->load('book:id,title,author'), 201);
    }

    public function myBorrows(Request $request, FineService $fineService)
    {
        $records = BorrowTransaction::with('book:id,title,author')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (BorrowTransaction $item) use ($fineService) {
                if (in_array($item->status, ['borrowed', 'overdue'], true)) {
                    $calcFine = $fineService->calculate($item->due_date);
                    if ($calcFine > 0 && $item->status !== 'overdue') {
                        $item->status = 'overdue';
                        $item->fine_amount = $calcFine;
                        $item->save();
                    }
                }

                return $item;
            });

        return response()->json($records);
    }

    public function returnBook(Request $request, BorrowTransaction $borrow, FineService $fineService, PaymentService $paymentService)
    {
        if ($borrow->user_id !== $request->user()->id && $request->user()->role?->name !== 'admin') {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        if ($borrow->status === 'returned') {
            return response()->json(['message' => 'Book already returned'], 422);
        }

        $result = DB::transaction(function () use ($borrow, $fineService, $paymentService) {
            $fine = $fineService->calculate($borrow->due_date, now()->toDateString());

            $borrow->update([
                'return_date' => now()->toDateString(),
                'fine_amount' => $fine,
                'status' => 'returned',
            ]);

            $borrow->book()->increment('available_copies');

            if ($fine > 0) {
                $paymentService->record($borrow->user, 'fine', $fine, 'online', $borrow->id);
            }

            return $borrow->fresh('book:id,title');
        });

        return response()->json($result);
    }

    public function readBorrowedBook(Request $request, BorrowTransaction $borrow, ExternalBookReaderService $readerService)
    {
        if ($borrow->user_id !== $request->user()->id && $request->user()->role?->name !== 'admin') {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        if (! in_array($borrow->status, ['borrowed', 'overdue'], true)) {
            return response()->json(['message' => 'Only active borrowed books can be read online'], 422);
        }

        $borrow->loadMissing('book:id,title,author,isbn');
        if (! $borrow->book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $reader = $readerService->resolve($borrow->book);
        $libraryLinks = $readerService->buildGlobalLibraryLinks($borrow->book);

        return response()->json([
            'borrow_id' => $borrow->id,
            'book' => [
                'id' => $borrow->book->id,
                'title' => $borrow->book->title,
                'author' => $borrow->book->author,
                'isbn' => $borrow->book->isbn,
            ],
            'reader' => $reader,
            'has_direct_reader' => (bool) ($reader && ! empty($reader['url'])),
            'library_links' => $libraryLinks,
            'message' => $reader && ! empty($reader['url'])
                ? 'Direct reader found.'
                : 'Direct reader unavailable. Use global library links.',
        ]);
    }

    public function reserve(Request $request, PaymentService $paymentService)
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'method' => ['nullable', 'in:cash,card,online'],
        ]);

        $fee = $validated['fee'] ?? 2.00;

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'book_id' => $validated['book_id'],
            'reservation_date' => now(),
            'expiry_date' => now()->addDays(2),
            'fee_amount' => $fee,
            'status' => 'pending',
        ]);

        $paymentService->record(
            $request->user(),
            'reservation',
            (float) $fee,
            $validated['method'] ?? 'online',
            $reservation->id
        );

        return response()->json($reservation->load('book:id,title'), 201);
    }

    public function ebooks()
    {
        return response()->json(Ebook::orderBy('title')->get());
    }

    public function storeEbook(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:150'],
            'rental_price' => ['required', 'numeric', 'min:0'],
            'rental_days' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'mimes:pdf,epub', 'max:10240'],
        ]);

        $filePath = $request->file('file')->store('ebooks', 'local');

        $ebook = Ebook::create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'file_path' => $filePath,
            'rental_price' => $validated['rental_price'],
            'rental_days' => $validated['rental_days'],
            'status' => 'active',
        ]);

        return response()->json($ebook, 201);
    }

    public function rentEbook(Request $request, Ebook $ebook, PaymentService $paymentService)
    {
        if ($ebook->status !== 'active') {
            return response()->json(['message' => 'E-book is currently unavailable'], 422);
        }

        $rental = EbookRental::create([
            'user_id' => $request->user()->id,
            'ebook_id' => $ebook->id,
            'rented_at' => now(),
            'expires_at' => now()->addDays($ebook->rental_days),
            'status' => 'active',
        ]);

        $paymentService->record($request->user(), 'ebook', (float) $ebook->rental_price, 'online', $rental->id);

        return response()->json($rental->load('ebook:id,title,author'), 201);
    }

    public function myRentals(Request $request)
    {
        $rentals = EbookRental::with('ebook:id,title,author,file_path')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($rentals);
    }

    public function downloadEbook(Request $request, EbookRental $rental)
    {
        if ($rental->user_id !== $request->user()->id && $request->user()->role?->name !== 'admin') {
            abort(403);
        }

        if (now()->greaterThan($rental->expires_at)) {
            $rental->update(['status' => 'expired']);
            abort(403, 'Rental has expired.');
        }

        return response()->download(storage_path('app/private/'.$rental->ebook->file_path));
    }

    public function payments(Request $request)
    {
        $query = Payment::with('user:id,name,email')->latest();

        if ($request->user()->role?->name !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->get());
    }

    public function revenueReport()
    {
        $rows = Payment::selectRaw('payment_type, SUM(amount) as total')
            ->where('status', 'paid')
            ->groupBy('payment_type')
            ->get();

        $monthly = Payment::selectRaw("strftime('%Y-%m', paid_at) as month, SUM(amount) as total")
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'by_type' => $rows,
            'by_month' => $monthly,
        ]);
    }

    public function sendDueReminders()
    {
        $upcoming = BorrowTransaction::with('user')
            ->whereIn('status', ['borrowed', 'overdue'])
            ->whereDate('due_date', '<=', now()->addDays(2)->toDateString())
            ->get();

        foreach ($upcoming as $item) {
            Notification::create([
                'user_id' => $item->user_id,
                'type' => now()->toDateString() > $item->due_date ? 'overdue_alert' : 'due_reminder',
                'message' => "Reminder: Book due date is {$item->due_date}.",
                'sent_at' => now(),
                'status' => 'sent',
            ]);
        }

        return response()->json(['message' => 'Reminders generated', 'count' => $upcoming->count()]);
    }

    public function myNotifications(Request $request)
    {
        return response()->json(
            Notification::where('user_id', $request->user()->id)->latest()->limit(50)->get()
        );
    }
}
