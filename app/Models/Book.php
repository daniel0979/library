<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'isbn',
        'title',
        'author',
        'category_id',
        'total_copies',
        'available_copies',
        'view_count',
        'shelf_location',
        'description',
        'cover_image_path',
    ];

    protected $appends = ['cover_image_url'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowTransactions()
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        return asset('storage/'.$this->cover_image_path);
    }
}
