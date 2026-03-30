<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Book;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use Eloquent for database-agnostic code
        $books = Book::where('is_read', true)
            ->whereNull('year_read')
            ->get();
        
        foreach ($books as $book) {
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
            
            // Save without triggering observer again
            $book->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally, you could clear year_read values if needed
        // But typically we don't reverse data migrations
    }
};
