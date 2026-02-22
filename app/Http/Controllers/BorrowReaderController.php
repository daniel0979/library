<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Services\ExternalBookReaderService;
use Illuminate\Http\Request;

class BorrowReaderController extends Controller
{
    public function show(Request $request, BorrowTransaction $borrow, ExternalBookReaderService $readerService)
    {
        $user = $request->user();
        $isOwner = $borrow->user_id === $user->id;
        $isAdmin = $user->role?->name === 'admin';

        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        if (! in_array($borrow->status, ['borrowed', 'overdue'], true)) {
            abort(422, 'Only active borrowed books can be opened in the reading room.');
        }

        $borrow->loadMissing('book:id,title,author,isbn,cover_image_path');
        if (! $borrow->book) {
            abort(404);
        }

        $reader = $readerService->resolve($borrow->book);
        $globalLinks = $readerService->buildGlobalLibraryLinks($borrow->book);

        $activeBorrows = BorrowTransaction::query()
            ->with('book:id,title,author')
            ->where('user_id', $borrow->user_id)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->latest()
            ->get();

        return view('borrowed.reader', [
            'borrow' => $borrow,
            'reader' => $reader,
            'globalLinks' => $globalLinks,
            'activeBorrows' => $activeBorrows,
        ]);
    }
}
