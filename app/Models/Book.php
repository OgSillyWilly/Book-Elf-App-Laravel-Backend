<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'type',
        'publisher',
        'publication_date',
        'cover_url',
        'cabinet',
        'shelf',
        'position',
        'is_read',
        'start_date',
        'end_date',
    ];
}
