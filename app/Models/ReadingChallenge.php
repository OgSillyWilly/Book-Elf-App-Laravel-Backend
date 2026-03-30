<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReadingChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'goal_books',
        'period_type',
        'start_date',
        'end_date',
        'is_active',
        'books_read',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'goal_books' => 'integer',
        'books_read' => 'integer',
    ];

    /**
     * Calculate current progress
     */
    public function calculateProgress(): int
    {
        $count = Book::where('is_read', true)
            ->whereBetween('end_date', [$this->start_date, $this->end_date])
            ->count();
        
        $this->books_read = $count;
        $this->save();
        
        return $count;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->goal_books === 0) {
            return 0;
        }
        
        return round(($this->books_read / $this->goal_books) * 100, 1);
    }

    /**
     * Get remaining books to read
     */
    public function getRemainingBooksAttribute(): int
    {
        return max(0, $this->goal_books - $this->books_read);
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute(): int
    {
        return max(0, Carbon::now()->diffInDays($this->end_date, false));
    }

    /**
     * Check if challenge is on track
     */
    public function getIsOnTrackAttribute(): bool
    {
        $totalDays = $this->start_date->diffInDays($this->end_date);
        $daysPassed = $this->start_date->diffInDays(Carbon::now());
        
        if ($totalDays === 0) {
            return true;
        }
        
        $expectedProgress = ($daysPassed / $totalDays) * $this->goal_books;
        return $this->books_read >= $expectedProgress;
    }

    /**
     * Get suggested unread books
     */
    public function getSuggestedBooks(int $limit = 10)
    {
        return Book::where('is_read', false)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
