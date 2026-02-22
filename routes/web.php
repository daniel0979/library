<?php

use App\Http\Controllers\Api\LibraryApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookCatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});

Route::get('/books', [BookCatalogController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookCatalogController::class, 'show'])->name('books.show');

Route::prefix('api/library')->group(function () {
    Route::get('/books', [LibraryApiController::class, 'books']);
    Route::get('/books/filters', [LibraryApiController::class, 'bookFilters']);
    Route::get('/plans', [LibraryApiController::class, 'plans']);
    Route::get('/currency/rates', [LibraryApiController::class, 'currencyRates']);
    Route::get('/ebooks', [LibraryApiController::class, 'ebooks']);
});

Route::middleware('auth')->prefix('api/library')->group(function () {
        Route::get('/summary', [LibraryApiController::class, 'summary']);
        Route::post('/books', [LibraryApiController::class, 'storeBook'])->middleware('role:admin');
        Route::put('/books/{book}', [LibraryApiController::class, 'updateBook'])->middleware('role:admin');
        Route::delete('/books/{book}', [LibraryApiController::class, 'deleteBook'])->middleware('role:admin');

        Route::post('/subscribe', [LibraryApiController::class, 'subscribe']);

        Route::post('/borrow', [LibraryApiController::class, 'borrow']);
        Route::get('/borrow/my', [LibraryApiController::class, 'myBorrows']);
        Route::post('/borrow/{borrow}/return', [LibraryApiController::class, 'returnBook']);

        Route::post('/reserve', [LibraryApiController::class, 'reserve']);

        Route::post('/ebooks', [LibraryApiController::class, 'storeEbook'])->middleware('role:admin');
        Route::post('/ebooks/{ebook}/rent', [LibraryApiController::class, 'rentEbook']);
        Route::get('/ebooks/my-rentals', [LibraryApiController::class, 'myRentals']);
        Route::get('/ebooks/rentals/{rental}/download', [LibraryApiController::class, 'downloadEbook']);

        Route::get('/payments', [LibraryApiController::class, 'payments']);
        Route::get('/reports/revenue', [LibraryApiController::class, 'revenueReport'])->middleware('role:admin');
        Route::get('/memberships/overview', [LibraryApiController::class, 'membershipOverview'])->middleware('role:admin');
        Route::get('/memberships/pending-payments', [LibraryApiController::class, 'pendingMembershipPayments'])->middleware('role:admin');
        Route::post('/memberships/pending-payments/{payment}/review', [LibraryApiController::class, 'reviewMembershipPayment'])->middleware('role:admin');

        Route::post('/notifications/due-reminders', [LibraryApiController::class, 'sendDueReminders'])->middleware('role:admin');
        Route::get('/notifications/my', [LibraryApiController::class, 'myNotifications']);
});
