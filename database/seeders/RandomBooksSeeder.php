<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RandomBooksSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Comedy' => [
                'Laugh Track at Midnight',
                'The Accidental Stand-Up',
                'Jokes in the Elevator',
                'Weekend of Bad Ideas',
                'The Seriously Funny Club',
            ],
            'Action' => [
                'Zero Hour Extraction',
                'Final Target: Skyline',
                'Steel Rain Protocol',
                'Chase Through Red Harbor',
                'Operation Burning Edge',
            ],
            'Thriller' => [
                'The Quiet Threat',
                'Last Signal on Channel Nine',
                'Cold Room Witness',
                'The Missing Briefcase',
                'Before the Lights Return',
            ],
            'Horror' => [
                'Whispers Beneath the Floor',
                'House at Hollow Lane',
                'The Lantern in the Attic',
                'Night Shift at Blackwood',
                'Echoes from Room Thirteen',
            ],
            'Romance' => [
                'Letters to Spring Street',
                'Coffee for Two at Dusk',
                'The Promise in Rain',
                'Our Last Summer Train',
                'When Hearts Return',
            ],
            'Fantasy' => [
                'The Crystal Gatekeeper',
                'Thrones of Emberfall',
                'Runes of the Northern Sea',
                'Song of the Moonblade',
                'The Last Dragon Cartographer',
            ],
            'Mystery' => [
                'Murder at Willow Station',
                'The Locked Gallery Case',
                'Footprints in Blue Dust',
                'Detective of Maple Street',
                'The Vanishing Auction',
            ],
            'Adventure' => [
                'Across the Seven Canyons',
                'Compass of the Lost Coast',
                'The Wild Trail Company',
                'River of Forgotten Maps',
                'Beyond the Iron Dunes',
            ],
            'Science Fiction' => [
                'Orbit of Silent Cities',
                'Neon Colony Uprising',
                'The Last Signal from Europa',
                'Quantum Drift Patrol',
                'Starlight Terminal 4',
            ],
            'Historical Fiction' => [
                'Winter Over Kingsbridge',
                'The Clockmaker of Harbor Town',
                'A Season of Paper Flags',
                'The General\'s Daughter',
                'When the Bells Were Bronze',
            ],
            'Original Fiction' => [
                'The Neon Orchard',
                'Paper Ships at Dawn',
                'Skyline of Quiet Storms',
                'Lanterns Over Cedar Bridge',
                'Crimson Bicycle Society',
                'Moonlit Baker of Row Street',
                'The Last Radio at Pine Hotel',
                'Cloud Harbor Mystery Club',
                'Electric Wind on Sunday',
                'Midnight Postcard to Tomorrow',
            ],
        ];

        $shelfCodes = [
            'Comedy' => 'COM',
            'Action' => 'ACT',
            'Thriller' => 'THR',
            'Horror' => 'HOR',
            'Romance' => 'ROM',
            'Fantasy' => 'FAN',
            'Mystery' => 'MYS',
            'Adventure' => 'ADV',
            'Science Fiction' => 'SCI',
            'Historical Fiction' => 'HIS',
            'Original Fiction' => 'ORI',
        ];

        $authorFirstNames = [
            'Alex', 'Jordan', 'Taylor', 'Morgan', 'Riley', 'Avery', 'Cameron', 'Parker', 'Quinn', 'Hayden',
            'Bailey', 'Rowan', 'Casey', 'Drew', 'Emerson', 'Kai', 'Skyler', 'Sawyer', 'Sydney', 'Jules',
        ];

        $authorLastNames = [
            'Hart', 'Bennett', 'Sinclair', 'Maddox', 'Cross', 'Wilder', 'Donovan', 'Pierce', 'Monroe', 'Shaw',
            'Quincy', 'Kensington', 'Vale', 'Carver', 'Sutton', 'Whitman', 'Marlowe', 'Sloane', 'Prescott', 'Rowe',
        ];

        $bookNumber = 1;

        foreach ($catalog as $genre => $titles) {
            $category = Category::firstOrCreate(['name' => $genre]);
            $shelfCode = $shelfCodes[$genre] ?? 'GEN';

            foreach ($titles as $title) {
                $firstName = $authorFirstNames[($bookNumber * 3) % count($authorFirstNames)];
                $lastName = $authorLastNames[($bookNumber * 7) % count($authorLastNames)];
                $author = "{$firstName} {$lastName}";

                $totalCopies = 2 + ($bookNumber % 6); // 2..7
                $isbn = '978'.str_pad((string) (1200000000 + $bookNumber), 10, '0', STR_PAD_LEFT);

                $book = Book::updateOrCreate(
                    ['isbn' => $isbn],
                    [
                        'title' => $title,
                        'author' => $author,
                        'category_id' => $category->id,
                        'total_copies' => $totalCopies,
                        'available_copies' => $totalCopies,
                        'view_count' => 0,
                        'shelf_location' => sprintf('%s-%02d', $shelfCode, $bookNumber),
                        'description' => "A {$genre} title from the demo catalog. Enjoy this story in your library collection.",
                    ]
                );

                $coverPath = $this->createCoverImage($isbn, $title, $author, $genre);
                if ($book->cover_image_path !== $coverPath) {
                    $book->cover_image_path = $coverPath;
                    $book->save();
                }

                $bookNumber++;
            }
        }
    }

    protected function createCoverImage(string $isbn, string $title, string $author, string $genre): string
    {
        $path = "books/covers/generated/{$isbn}.svg";
        $svg = $this->buildCoverSvg($title, $author, $genre, $isbn);
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    protected function buildCoverSvg(string $title, string $author, string $genre, string $isbn): string
    {
        $palettes = [
            ['#0f172a', '#1d4ed8', '#38bdf8'],
            ['#1f2937', '#7c2d12', '#fb923c'],
            ['#0f172a', '#14532d', '#4ade80'],
            ['#111827', '#5b21b6', '#a78bfa'],
            ['#1e293b', '#9f1239', '#fb7185'],
            ['#172554', '#155e75', '#67e8f9'],
            ['#312e81', '#7c3aed', '#c4b5fd'],
            ['#1f2937', '#b45309', '#facc15'],
            ['#111827', '#0f766e', '#2dd4bf'],
            ['#1e1b4b', '#7f1d1d', '#fca5a5'],
        ];

        $palette = $palettes[abs(crc32($genre.$isbn)) % count($palettes)];
        [$bgStart, $bgEnd, $accent] = $palette;

        $genreEscaped = $this->escapeXml($genre);
        $authorEscaped = $this->escapeXml($author);
        $isbnEscaped = $this->escapeXml($isbn);
        $titleLines = $this->splitTitleLines($title, 22, 3);

        $titleText = '';
        $startY = 270;
        foreach ($titleLines as $idx => $line) {
            $lineEscaped = $this->escapeXml($line);
            $y = $startY + ($idx * 50);
            $titleText .= "<text x=\"42\" y=\"{$y}\" font-family=\"Georgia, serif\" font-size=\"40\" font-weight=\"700\" fill=\"#ffffff\">{$lineEscaped}</text>\n";
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="640" viewBox="0 0 420 640" role="img" aria-label="Book cover for {$this->escapeXml($title)}">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{$bgStart}"/>
            <stop offset="100%" stop-color="{$bgEnd}"/>
        </linearGradient>
        <linearGradient id="glow" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="{$accent}" stop-opacity="0"/>
            <stop offset="50%" stop-color="{$accent}" stop-opacity="0.65"/>
            <stop offset="100%" stop-color="{$accent}" stop-opacity="0"/>
        </linearGradient>
    </defs>
    <rect width="420" height="640" fill="url(#bg)"/>
    <rect x="0" y="110" width="420" height="8" fill="url(#glow)"/>
    <rect x="0" y="560" width="420" height="6" fill="url(#glow)"/>
    <circle cx="350" cy="95" r="55" fill="{$accent}" fill-opacity="0.18"/>
    <circle cx="55" cy="590" r="66" fill="{$accent}" fill-opacity="0.15"/>
    <text x="42" y="84" font-family="Arial, sans-serif" font-size="22" font-weight="700" fill="#e2e8f0" letter-spacing="2">{$genreEscaped}</text>
{$titleText}    <text x="42" y="526" font-family="Arial, sans-serif" font-size="22" font-weight="600" fill="#e2e8f0">{$authorEscaped}</text>
    <text x="42" y="592" font-family="Arial, sans-serif" font-size="14" fill="#cbd5e1">ISBN {$isbnEscaped}</text>
    <rect x="36" y="36" width="348" height="568" rx="10" ry="10" fill="none" stroke="#ffffff" stroke-opacity="0.23" stroke-width="2"/>
</svg>
SVG;
    }

    protected function splitTitleLines(string $title, int $maxLength, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        if (empty($words)) {
            return ['Untitled'];
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : "{$current} {$word}";
            if (strlen($candidate) <= $maxLength) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = $lines[$maxLines - 1];
            if (strlen($last) > $maxLength - 3) {
                $last = substr($last, 0, $maxLength - 3);
            }
            $lines[$maxLines - 1] = rtrim($last)."...";
        }

        return $lines;
    }

    protected function escapeXml(string $value): string
    {
        return str_replace(
            ['&', '"', "'", '<', '>'],
            ['&amp;', '&quot;', '&apos;', '&lt;', '&gt;'],
            $value
        );
    }
}
