<?php

namespace App\Observers;

use App\Models\Book;

class BookObserver
{
    /**
     * Handle the Book "saving" event.
     * Auto-populate year_read when book is marked as read
     */
    public function saving(Book $book): void
    {
        // If book is marked as read and year_read is not set
        if ($book->is_read && !$book->year_read) {
            // Try to get year from end_date
            if ($book->end_date) {
                $book->year_read = $book->end_date->year;
            }
            // Otherwise try start_date
            elseif ($book->start_date) {
                $book->year_read = $book->start_date->year;
            }
            // Otherwise use current year
            else {
                $book->year_read = now()->year;
            }
        }
        
        // If book is marked as not read, clear year_read
        if (!$book->is_read && $book->isDirty('is_read')) {
            $book->year_read = null;
        }
    }
}
