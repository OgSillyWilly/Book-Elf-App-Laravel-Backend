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
        'series_id',
        'series_order',
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
        'rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'publication_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_read' => 'boolean',
        'has_slipcase' => 'boolean',
        'has_dustjacket' => 'boolean',
        'year_read' => 'integer',
        'position' => 'integer',
        'series_id' => 'integer',
        'series_order' => 'integer',
        'rating' => 'decimal:1',
    ];

    /**
     * Get the series that this book belongs to
     */
    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}
