@extends('layouts.app', ['title' => 'Reading Room'])

@section('content')
<div class="space-y-5 reader-shell">
    <section class="reader-hero rounded-2xl p-5 sm:p-6 text-slate-900" data-reveal>
        <div class="relative z-[1] flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Borrowed Book Reading Room</p>
                <h1 class="text-2xl sm:text-3xl font-semibold leading-tight">{{ $borrow->book->title }}</h1>
                <p class="text-sm text-slate-700">{{ $borrow->book->author }}{{ $borrow->book->isbn ? ' | ISBN '.$borrow->book->isbn : '' }}</p>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="reader-pill">Status: {{ strtoupper($borrow->status) }}</span>
                    <span class="reader-pill">Due: {{ $borrow->due_date }}</span>
                    <span class="reader-pill">Borrow ID: #{{ $borrow->id }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if($borrow->book->cover_image_url)
                    <img src="{{ $borrow->book->cover_image_url }}" alt="{{ $borrow->book->title }} cover" class="h-28 w-20 rounded-lg border border-slate-200 bg-white object-contain p-1 shadow-sm">
                @endif
                <a href="{{ route('books.show', $borrow->book) }}" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-sm hover:bg-slate-800">Back to Book</a>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-[1.75fr,1fr]">
        <article class="reader-panel p-4 sm:p-5 space-y-3" data-reveal data-reveal-delay="70">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-lg">Live Reader</h2>
                @if($reader && !empty($reader['url']))
                    <a href="{{ $reader['url'] }}" target="_blank" rel="noopener noreferrer" class="rounded bg-cyan-700 text-white px-3 py-1.5 text-xs hover:bg-cyan-800">Open in New Tab</a>
                @endif
            </div>

            @if($reader && !empty($reader['url']))
                <p class="text-sm text-slate-600">
                    Source: <span class="font-medium">{{ str_replace('_', ' ', strtoupper($reader['provider'] ?? 'external')) }}</span>
                    @if(!empty($reader['access_type']))
                        | Access: {{ strtoupper($reader['access_type']) }}
                    @endif
                </p>
                <div class="reader-frame-wrap">
                    <iframe
                        class="reader-iframe"
                        src="{{ $reader['url'] }}"
                        title="Reader for {{ $borrow->book->title }}"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                        allowfullscreen
                    ></iframe>
                </div>
                <p class="text-xs text-slate-500">If this provider blocks embedding, use "Open in New Tab".</p>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="font-medium text-amber-900">Direct online reader is not available for this title right now.</p>
                    <p class="text-sm text-amber-800 mt-1">Use the global library sources below to find readable or borrowable editions.</p>
                </div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="reader-panel p-4" data-reveal data-reveal-delay="120">
                <h2 class="font-semibold text-lg mb-2">My Active Borrowed Books</h2>
                <div class="space-y-2 max-h-72 overflow-auto pr-1">
                    @forelse($activeBorrows as $item)
                        <a href="{{ route('borrowed.reader', $item) }}" class="reader-mini-card {{ $item->id === $borrow->id ? 'is-active' : '' }}">
                            <span class="font-medium text-sm">{{ $item->book?->title ?? 'Unknown book' }}</span>
                            <span class="text-xs text-slate-500">{{ strtoupper($item->status) }} | Due {{ $item->due_date }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-slate-600">No active borrowed books.</p>
                    @endforelse
                </div>
            </article>

            <article class="reader-panel p-4" data-reveal data-reveal-delay="160">
                <h2 class="font-semibold text-lg mb-2">Tip</h2>
                <p class="text-sm text-slate-600">Use ISBN + title + author in your records for better source matching.</p>
            </article>
        </aside>
    </section>

    <section class="reader-panel p-4 sm:p-5" data-reveal data-reveal-delay="200">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-lg">Global Free Library Sources</h2>
            <span class="text-xs text-slate-500">Worldwide search and reading links</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($globalLinks as $index => $link)
                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="reader-link-card reader-link-card-inline"
                    data-reveal
                    data-reveal-delay="{{ 240 + ($index * 35) }}"
                >
                    <span class="text-xs uppercase tracking-wide text-cyan-700">{{ strtoupper($link['type'] ?? 'source') }}</span>
                    <span class="font-medium text-slate-900">{{ $link['label'] ?? 'Library Source' }}</span>
                    <span class="text-sm text-slate-600">Open source catalog or reader for this book.</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
