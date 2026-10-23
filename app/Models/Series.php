<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'total_books',
        'author',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'total_books' => 'integer',
    ];

    /**
     * Get the books in this series
     */
    public function books()
    {
        return $this->hasMany(Book::class)->orderBy('series_order');
    }

    /**
     * Get read books in this series
     */
    public function readBooks()
    {
        return $this->hasMany(Book::class)->where('is_read', true)->orderBy('series_order');
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalBooks = $this->books()->count();
        if ($totalBooks === 0) {
            return 0;
        }
        
        $readBooks = $this->readBooks()->count();
        return round(($readBooks / $totalBooks) * 100, 1);
    }

    /**
     * Get books read count
     */
    public function getBooksReadCountAttribute(): int
    {
        return $this->readBooks()->count();
    }

    /**
     * Get total books count
     */
    public function getTotalBooksCountAttribute(): int
    {
        return $this->books()->count();
    }
}
