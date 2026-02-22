<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class LibraryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $fiction = Category::firstOrCreate(['name' => 'Fiction']);
        $it = Category::firstOrCreate(['name' => 'Information Technology']);

        Book::updateOrCreate(
            ['isbn' => '9780132350884'],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'category_id' => $it->id,
                'total_copies' => 4,
                'available_copies' => 4,
                'shelf_location' => 'IT-A1',
            ]
        );

        Book::updateOrCreate(
            ['isbn' => '9780743273565'],
            [
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'category_id' => $fiction->id,
                'total_copies' => 3,
                'available_copies' => 3,
                'shelf_location' => 'FIC-B4',
            ]
        );
    }
}
