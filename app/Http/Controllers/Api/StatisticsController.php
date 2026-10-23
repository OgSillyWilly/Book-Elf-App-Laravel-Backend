<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Cache a per-user statistic and remember its key so clearCache() can drop it
     */
    private function remember(string $key, \Closure $callback)
    {
        $userId = Auth::id();
        $fullKey = "statistics:{$userId}:{$key}";
        $trackerKey = "statistics_keys:{$userId}";

        $keys = Cache::get($trackerKey, []);
        if (!in_array($fullKey, $keys)) {
            $keys[] = $fullKey;
            Cache::put($trackerKey, $keys, 3600);
        }

        return Cache::remember($fullKey, 300, $callback);
    }

    /**
     * Get overall reading statistics
     */
    public function overview()
    {
        return $this->remember('overview', function () {
            $totalBooks = Book::count();
            $booksRead = Book::where('is_read', true)->count();
            $booksUnread = $totalBooks - $booksRead;
            
            $currentYear = date('Y');
            $booksThisYear = Book::where('is_read', true)
                ->where('year_read', $currentYear)
                ->count();
            
            $avgRating = Book::where('is_read', true)
                ->whereNotNull('rating')
                ->avg('rating');
            
            // Reading streak
            $recentBooks = Book::where('is_read', true)
                ->whereNotNull('end_date')
                ->orderBy('end_date', 'desc')
                ->take(365)
                ->pluck('end_date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'));
            
            $currentStreak = 0;
            $lastDate = Carbon::now();
            foreach ($recentBooks as $date) {
                $bookDate = Carbon::parse($date);
                if ($bookDate->diffInDays($lastDate) <= 7) {
                    $currentStreak++;
                    $lastDate = $bookDate;
                } else {
                    break;
                }
            }
            
            return [
                'total_books' => $totalBooks,
                'books_read' => $booksRead,
                'books_unread' => $booksUnread,
                'books_this_year' => $booksThisYear,
                'average_rating' => round($avgRating ?? 0, 2),
                'current_streak' => $currentStreak,
                'reading_percentage' => $totalBooks > 0 ? round(($booksRead / $totalBooks) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get author progress statistics
     */
    public function authorProgress(Request $request)
    {
        $author = $request->input('author');
        
        if (!$author) {
            return response()->json(['error' => 'Author parameter is required'], 400);
        }
        
        return $this->remember('author:' . md5($author), function () use ($author) {
            $totalBooks = Book::where('author', 'LIKE', "%{$author}%")->count();
            $booksRead = Book::where('author', 'LIKE', "%{$author}%")
                ->where('is_read', true)
                ->count();
            
            $avgRating = Book::where('author', 'LIKE', "%{$author}%")
                ->where('is_read', true)
                ->whereNotNull('rating')
                ->avg('rating');
            
            $recentlyRead = Book::where('author', 'LIKE', "%{$author}%")
                ->where('is_read', true)
                ->whereNotNull('end_date')
                ->orderBy('end_date', 'desc')
                ->take(5)
                ->get(['title', 'end_date', 'rating']);
            
            $unread = Book::where('author', 'LIKE', "%{$author}%")
                ->where('is_read', false)
                ->get(['title', 'isbn', 'series_id', 'series_order']);
            
            return [
                'author' => $author,
                'total_books' => $totalBooks,
                'books_read' => $booksRead,
                'books_unread' => $totalBooks - $booksRead,
                'progress_percentage' => $totalBooks > 0 ? round(($booksRead / $totalBooks) * 100, 1) : 0,
                'average_rating' => round($avgRating ?? 0, 2),
                'recently_read' => $recentlyRead,
                'unread_books' => $unread,
            ];
        });
    }

    /**
     * Get reading patterns by month
     */
    public function readingPatterns(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        return $this->remember("patterns:{$year}", function () use ($year) {
            $monthlyStats = [];
            
            for ($month = 1; $month <= 12; $month++) {
                $count = Book::where('is_read', true)
                    ->whereYear('end_date', $year)
                    ->whereMonth('end_date', $month)
                    ->count();
                
                $monthlyStats[] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('F'),
                    'books_read' => $count,
                ];
            }
            
            $yearTotal = Book::where('is_read', true)
                ->where('year_read', $year)
                ->count();
            
            return [
                'year' => $year,
                'total_books' => $yearTotal,
                'monthly_breakdown' => $monthlyStats,
                'average_per_month' => round($yearTotal / 12, 1),
            ];
        });
    }

    /**
     * Get reading statistics by type (hardback, paperback, ebook)
     */
    public function byType()
    {
        return $this->remember('by_type', function () {
            $stats = Book::where('is_read', true)
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');
            
            return [
                'hardback' => $stats->get('hardback')?->count ?? 0,
                'paperback' => $stats->get('paperback')?->count ?? 0,
                'ebook' => $stats->get('ebook')?->count ?? 0,
            ];
        });
    }

    /**
     * Get top rated books
     */
    public function topRated(Request $request)
    {
        $limit = min((int)$request->input('limit', 10), 50);
        
        return $this->remember("top_rated:{$limit}", function () use ($limit) {
            return Book::where('is_read', true)
                ->whereNotNull('rating')
                ->orderBy('rating', 'desc')
                ->orderBy('end_date', 'desc')
                ->take($limit)
                ->get(['title', 'author', 'rating', 'end_date', 'cover_url']);
        });
    }

    /**
     * Get reading history grouped by year
     */
    public function yearlyHistory()
    {
        return $this->remember('yearly_history', function () {
            $history = Book::where('is_read', true)
                ->whereNotNull('year_read')
                ->select('year_read', DB::raw('COUNT(*) as count'))
                ->groupBy('year_read')
                ->orderBy('year_read', 'desc')
                ->get();
            
            return $history->map(function ($item) {
                return [
                    'year' => $item->year_read,
                    'books_count' => $item->count,
                ];
            });
        });
    }

    /**
     * Get series completion statistics
     */
    public function seriesProgress()
    {
        return $this->remember('series_progress', function () {
            $series = \App\Models\Series::with('books')->get();
            
            $stats = $series->map(function ($s) {
                $totalBooks = $s->total_books;
                $booksRead = $s->books->where('is_read', true)->count();
                $progress = $totalBooks > 0 ? round(($booksRead / $totalBooks) * 100, 1) : 0;
                
                return [
                    'series_name' => $s->name,
                    'author' => $s->author,
                    'total_books' => $totalBooks,
                    'books_read' => $booksRead,
                    'progress_percentage' => $progress,
                    'is_completed' => $booksRead >= $totalBooks,
                ];
            });
            
            return [
                'total_series' => $series->count(),
                'completed_series' => $stats->where('is_completed', true)->count(),
                'in_progress_series' => $stats->where('progress_percentage', '>', 0)
                    ->where('is_completed', false)->count(),
                'series' => $stats,
            ];
        });
    }

    /**
     * Clear the current user's statistics cache
     */
    public function clearCache()
    {
        $trackerKey = 'statistics_keys:' . Auth::id();

        foreach (Cache::get($trackerKey, []) as $key) {
            Cache::forget($key);
        }
        Cache::forget($trackerKey);

        return response()->json(['message' => 'Statistics cache cleared successfully']);
    }
}
