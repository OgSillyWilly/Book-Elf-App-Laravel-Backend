<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeriesController extends Controller
{
    /**
     * Display a listing of series with progress
     */
    public function index(Request $request)
    {
        $query = Series::query()->withCount(['books', 'readBooks']);

        // Search
        if ($request->filled('search')) {
            $search = addcslashes(trim($request->input('search')), '%_');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Filter by author
        if ($request->filled('author')) {
            $query->where('author', $request->input('author'));
        }

        // Filter by completion status
        if ($request->filled('is_completed')) {
            $query->where('is_completed', filter_var($request->input('is_completed'), FILTER_VALIDATE_BOOLEAN));
        }

        $series = $query->orderBy('name')->paginate(50);

        // Add progress percentage to each series
        $series->getCollection()->transform(function ($serie) {
            $serie->progress_percentage = $serie->books_count > 0 
                ? round(($serie->read_books_count / $serie->books_count) * 100, 1) 
                : 0;
            return $serie;
        });

        return response()->json($series);
    }

    /**
     * Store a newly created series
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_books' => 'nullable|integer|min:0',
            'author' => 'nullable|string|max:255',
            'is_completed' => 'nullable|boolean',
        ]);

        $series = Series::create($validated);

        return response()->json($series, 201);
    }

    /**
     * Display the specified series with books
     */
    public function show(Series $series)
    {
        $series->load(['books' => function ($query) {
            $query->orderBy('series_order');
        }]);

        $series->progress_percentage = $series->progress_percentage;
        $series->books_read_count = $series->books_read_count;
        $series->total_books_count = $series->total_books_count;

        return response()->json($series);
    }

    /**
     * Update the specified series
     */
    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_books' => 'nullable|integer|min:0',
            'author' => 'nullable|string|max:255',
            'is_completed' => 'nullable|boolean',
        ]);

        $series->update($validated);

        return response()->json($series);
    }

    /**
     * Remove the specified series
     */
    public function destroy(Series $series)
    {
        // This will set series_id to null in books due to onDelete('set null')
        $series->delete();

        return response()->json(null, 204);
    }

    /**
     * Get all books in a series
     */
    public function books(Series $series)
    {
        $books = $series->books()->with('series')->get();
        return response()->json($books);
    }

    /**
     * Get series progress statistics
     */
    public function progress(Series $series)
    {
        $totalBooks = $series->total_books_count;
        $readBooks = $series->books_read_count;
        $unreadBooks = $totalBooks - $readBooks;

        $nextUnread = $series->books()
            ->where('is_read', false)
            ->orderBy('series_order')
            ->first();

        return response()->json([
            'series_id' => $series->id,
            'series_name' => $series->name,
            'total_books' => $totalBooks,
            'books_read' => $readBooks,
            'books_unread' => $unreadBooks,
            'progress_percentage' => $series->progress_percentage,
            'next_unread_book' => $nextUnread,
            'is_completed' => $readBooks === $totalBooks && $totalBooks > 0,
        ]);
    }
}
