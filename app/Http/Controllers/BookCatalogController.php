<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BookCatalogController extends Controller
{
    public function index()
    {
        return view('books.index');
    }

    public function show(Book $book)
    {
        $book->increment('view_count');
        $book->refresh();
        $book->load('category:id,name');

        return view('books.show', compact('book'));
    }
}
