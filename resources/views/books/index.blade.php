@extends('layouts.app', ['title' => 'All Books'])

@section('content')
<section class="space-y-6" id="booksCatalog" data-reveal>
    <div class="rounded-2xl bg-white/90 border border-slate-200 p-5 shadow-sm" data-reveal data-reveal-delay="80">
        <div class="flex flex-col md:flex-row md:items-end gap-3">
            <div class="flex-1">
                <label for="bookSearch" class="block text-sm font-medium mb-1">Search Books</label>
                <input id="bookSearch" type="text" placeholder="Search by title, author, or ISBN..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white">
            </div>
            <div class="w-full md:w-52">
                <label for="bookCategory" class="block text-sm font-medium mb-1">Category</label>
                <select id="bookCategory" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 bg-white">
                    <option value="all">All categories</option>
                </select>
            </div>
            <div class="w-full md:w-44">
                <label for="bookAvailability" class="block text-sm font-medium mb-1">Availability</label>
                <select id="bookAvailability" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 bg-white">
                    <option value="all">All</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <div class="w-full md:w-44">
                <label for="bookSort" class="block text-sm font-medium mb-1">Sort</label>
                <select id="bookSort" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 bg-white">
                    <option value="latest">Latest</option>
                    <option value="title_asc">Title A-Z</option>
                    <option value="title_desc">Title Z-A</option>
                    <option value="author_asc">Author A-Z</option>
                    <option value="author_desc">Author Z-A</option>
                </select>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between" data-reveal data-reveal-delay="140">
        <h2 class="font-semibold text-slate-800">Library Collection</h2>
        <p class="text-sm text-slate-500"><span id="booksCount">0</span> books found</p>
    </div>

    <div id="booksGrid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-reveal data-reveal-delay="200"></div>
</section>
@endsection

