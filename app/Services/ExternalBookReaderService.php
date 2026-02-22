<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExternalBookReaderService
{
    public function resolve(Book $book): ?array
    {
        $isbn = $this->normalizeIsbn($book->isbn);
        $query = $this->buildQuery($book);

        if (! $isbn && ! $query) {
            return null;
        }

        return Cache::remember(
            'book-reader-url:'.sha1(($isbn ?? '').'|'.($query ?? '')),
            now()->addHours(12),
            fn () => $this->fetchReaderData($isbn, $query)
        );
    }

    public function buildGlobalLibraryLinks(Book $book): array
    {
        $isbn = $this->normalizeIsbn($book->isbn);
        $query = $this->buildQuery($book) ?? trim((string) $book->title);
        $query = $query !== '' ? $query : 'book';

        $queryParam = rawurlencode($query);
        $archiveQuery = rawurlencode($query.' AND mediatype:texts');
        $isbnUrl = $isbn ? 'https://openlibrary.org/isbn/'.rawurlencode($isbn) : null;

        return array_values(array_filter([
            [
                'provider' => 'open_library',
                'label' => 'Open Library',
                'url' => "https://openlibrary.org/search?q={$queryParam}",
                'type' => 'free',
            ],
            [
                'provider' => 'internet_archive',
                'label' => 'Internet Archive',
                'url' => "https://archive.org/search?query={$archiveQuery}",
                'type' => 'free',
            ],
            [
                'provider' => 'project_gutenberg',
                'label' => 'Project Gutenberg',
                'url' => "https://www.gutenberg.org/ebooks/search/?query={$queryParam}",
                'type' => 'free',
            ],
            [
                'provider' => 'hathitrust',
                'label' => 'HathiTrust',
                'url' => "https://catalog.hathitrust.org/Search/Home?lookfor={$queryParam}&searchtype=all",
                'type' => 'catalog',
            ],
            [
                'provider' => 'worldcat',
                'label' => 'WorldCat',
                'url' => "https://search.worldcat.org/search?q={$queryParam}",
                'type' => 'catalog',
            ],
            [
                'provider' => 'google_books',
                'label' => 'Google Books',
                'url' => "https://books.google.com/books?q={$queryParam}",
                'type' => 'preview',
            ],
            $isbnUrl ? [
                'provider' => 'open_library_isbn',
                'label' => 'Open Library ISBN',
                'url' => $isbnUrl,
                'type' => 'free',
            ] : null,
        ]));
    }

    protected function fetchReaderData(?string $isbn, ?string $query): ?array
    {
        if ($isbn) {
            $openLibraryIsbn = $this->fromOpenLibrary("isbn:{$isbn}");
            if ($openLibraryIsbn) {
                return $openLibraryIsbn;
            }

            $googleIsbn = $this->fromGoogleBooks("isbn:{$isbn}");
            if ($googleIsbn) {
                return $googleIsbn;
            }
        }

        if ($query) {
            $openLibraryQuery = $this->fromOpenLibrary($query);
            if ($openLibraryQuery) {
                return $openLibraryQuery;
            }

            $googleQuery = $this->fromGoogleBooks($query);
            if ($googleQuery) {
                return $googleQuery;
            }
        }

        return null;
    }

    protected function fromGoogleBooks(string $searchQuery): ?array
    {
        $query = [
            'q' => $searchQuery,
            'maxResults' => 1,
        ];

        $apiKey = config('services.google_books.key');
        if (! empty($apiKey)) {
            $query['key'] = $apiKey;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://www.googleapis.com/books/v1/volumes', $query);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $item = $response->json('items.0');
        if (! is_array($item)) {
            return null;
        }

        $volumeInfo = $item['volumeInfo'] ?? [];
        $accessInfo = $item['accessInfo'] ?? [];

        $viewability = strtolower((string) ($accessInfo['viewability'] ?? 'unknown'));
        $url = $accessInfo['webReaderLink']
            ?? $volumeInfo['previewLink']
            ?? null;

        if (! $url || $viewability === 'no_pages') {
            return null;
        }

        $accessType = match ($viewability) {
            'all_pages' => 'full',
            'partial' => 'preview',
            'no_pages' => 'none',
            default => 'unknown',
        };

        return [
            'provider' => 'google_books',
            'url' => $url,
            'access_type' => $accessType,
            'viewability' => $viewability,
            'embeddable' => (bool) ($accessInfo['embeddable'] ?? false),
        ];
    }

    protected function fromOpenLibrary(string $searchQuery): ?array
    {
        $appName = config('app.name', 'LibraryApp');
        $contact = trim((string) config('services.open_library.contact', ''));
        $userAgent = $contact !== ''
            ? "{$appName} ({$contact})"
            : "{$appName} (".config('app.url', 'https://localhost').")";

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                ])
                ->acceptJson()
                ->get('https://openlibrary.org/search.json', [
                    'q' => $searchQuery,
                    'fields' => 'key,availability',
                    'availability' => 'true',
                    'limit' => 1,
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $doc = $response->json('docs.0');
        if (! is_array($doc)) {
            return null;
        }

        $availability = $doc['availability'] ?? [];

        $url = $availability['borrow_url']
            ?? $availability['preview_url']
            ?? null;

        if (! $url && ! empty($doc['key'])) {
            $url = 'https://openlibrary.org'.$doc['key'];
        }

        if (! $url) {
            return null;
        }

        $accessType = ! empty($availability['borrow_url'])
            ? 'borrow'
            : (! empty($availability['is_readable']) ? 'read' : 'preview');

        return [
            'provider' => 'open_library',
            'url' => $url,
            'access_type' => $accessType,
            'is_readable' => (bool) ($availability['is_readable'] ?? false),
        ];
    }

    protected function buildQuery(Book $book): ?string
    {
        $parts = array_filter([
            trim((string) $book->title),
            trim((string) $book->author),
        ], fn ($part) => $part !== '');

        if (empty($parts)) {
            return null;
        }

        return implode(' ', $parts);
    }

    protected function normalizeIsbn(?string $isbn): ?string
    {
        if (! is_string($isbn) || trim($isbn) === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (! $clean) {
            return null;
        }

        return strtoupper($clean);
    }
}
