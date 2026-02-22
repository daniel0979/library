<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbookRental extends Model
{
    protected $fillable = [
        'user_id',
        'ebook_id',
        'rented_at',
        'expires_at',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }
}
