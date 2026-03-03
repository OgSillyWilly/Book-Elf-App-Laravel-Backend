<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'type',
        'publisher',
        'publication_date',
        'cover_url',
        'has_slipcase',
        'has_dustjacket',
        'cabinet',
        'shelf',
        'position',
        'is_read',
        'start_date',
        'end_date',
        'year_read',
    ];
}
