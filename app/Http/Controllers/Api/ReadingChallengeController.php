<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingChallenge;
use App\Models\Book;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReadingChallengeController extends Controller
{
    /**
     * Display a listing of challenges
     */
    public function index()
    {
        $challenges = ReadingChallenge::orderBy('is_active', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        // Add calculated attributes
        $challenges->each(function ($challenge) {
            $challenge->calculateProgress();
            $challenge->progress_percentage = $challenge->progress_percentage;
            $challenge->remaining_books = $challenge->remaining_books;
            $challenge->days_remaining = $challenge->days_remaining;
            $challenge->is_on_track = $challenge->is_on_track;
        });

        return response()->json($challenges);
    }

    /**
     * Store a newly created challenge
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_books' => 'required|integer|min:1',
            'period_type' => 'required|in:monthly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $challenge = ReadingChallenge::create($validated);
        $challenge->calculateProgress();

        return response()->json($challenge, 201);
    }

    /**
     * Display the specified challenge with detailed progress
     */
    public function show(ReadingChallenge $challenge)
    {
        $challenge->calculateProgress();
        
        // Get books read during challenge period
        $booksRead = Book::where('is_read', true)
            ->whereBetween('end_date', [$challenge->start_date, $challenge->end_date])
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'challenge' => $challenge,
            'progress_percentage' => $challenge->progress_percentage,
            'remaining_books' => $challenge->remaining_books,
            'days_remaining' => $challenge->days_remaining,
            'is_on_track' => $challenge->is_on_track,
            'books_read' => $booksRead,
        ]);
    }

    /**
     * Update the specified challenge
     */
    public function update(Request $request, ReadingChallenge $challenge)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_books' => 'required|integer|min:1',
            'period_type' => 'required|in:monthly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $challenge->update($validated);
        $challenge->calculateProgress();

        return response()->json($challenge);
    }

    /**
     * Remove the specified challenge
     */
    public function destroy(ReadingChallenge $challenge)
    {
        $challenge->delete();
        return response()->json(null, 204);
    }

    /**
     * Get suggested books for the challenge
     */
    public function suggestions(ReadingChallenge $challenge, Request $request)
    {
        $limit = min((int)$request->input('limit', 10), 50);
        
        // Get unread books, prioritize books not in incomplete series
        $suggestions = Book::where('is_read', false)
            ->whereDoesntHave('series', function ($query) {
                // Exclude books from series where earlier books aren't read
                $query->whereHas('books', function ($q) {
                    $q->where('is_read', false)
                      ->whereColumn('series_order', '<', 'books.series_order');
                });
            })
            ->inRandomOrder()
            ->limit($limit)
            ->with('series')
            ->get();

        return response()->json([
            'challenge' => [
                'id' => $challenge->id,
                'name' => $challenge->name,
                'remaining_books' => $challenge->remaining_books,
                'days_remaining' => $challenge->days_remaining,
            ],
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get active challenge
     */
    public function active()
    {
        $challenge = ReadingChallenge::where('is_active', true)
            ->where('end_date', '>=', Carbon::now())
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$challenge) {
            return response()->json(['message' => 'No active challenge found'], 404);
        }

        $challenge->calculateProgress();
        $challenge->progress_percentage = $challenge->progress_percentage;
        $challenge->remaining_books = $challenge->remaining_books;
        $challenge->days_remaining = $challenge->days_remaining;
        $challenge->is_on_track = $challenge->is_on_track;

        return response()->json($challenge);
    }
}
