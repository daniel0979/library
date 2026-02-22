<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ebook extends Model
{
    protected $fillable = [
        'title',
        'author',
        'file_path',
        'rental_price',
        'rental_days',
        'status',
    ];

    public function rentals()
    {
        return $this->hasMany(EbookRental::class);
    }
}
