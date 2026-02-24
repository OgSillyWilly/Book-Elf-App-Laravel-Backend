<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    private function transformCoverUrl($book)
    {
        if ($book->cover_url && filter_var($book->cover_url, FILTER_VALIDATE_URL)) {
            $book->cover_url = url('/api/image-proxy?url=' . urlencode($book->cover_url));
        }
        return $book;
    }

    // Display a listing of the resource.
    public function index(Request $request)
    {
        // Cache key based on query parameters
        $cacheKey = 'books_' . md5(json_encode($request->all()));
        
        // Cache for 5 minutes
        $books = cache()->remember($cacheKey, 300, function () use ($request) {
            $query = Book::query();

            // Search
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%");
                });
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            // Filter by read status
            if ($request->has('is_read')) {
                $query->where('is_read', $request->input('is_read'));
            }

            // Sort
            $sortBy = $request->input('sort_by', 'title');
            $query->orderBy($sortBy);

            return $query->get()->map(function ($book) {
                return $this->transformCoverUrl($book);
            });
        });
        
        return response()->json($books);
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:255',
            'isbn' => 'nullable|max:20',
            'type' => 'nullable|max:50',
            'publisher' => 'nullable|max:255',
            'publication_date' => 'nullable|date',
            'cover_url' => 'nullable|url|max:2048',
            'cabinet' => 'nullable|max:50',
            'shelf' => 'nullable|max:10',
            'position' => 'nullable|integer',
            'is_read' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $book = Book::create($validated);
        
        // Clear cache when creating a book
        cache()->flush();
        
        return response()->json($this->transformCoverUrl($book), 201);
    }

    // Display the specified resource.
    public function show(string $id)
    {
        $book = Book::findOrFail($id);
        return response()->json($this->transformCoverUrl($book));
    }

    //Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:255',
            'isbn' => 'nullable|max:20',
            'type' => 'nullable|max:50',
            'publisher' => 'nullable|max:255',
            'publication_date' => 'nullable|date',
            'cover_url' => 'nullable|url|max:2048',
            'has_slipcase' => 'nullable|boolean',
            'has_dustjacket' => 'nullable|boolean',
            'cabinet' => 'nullable|max:50',
            'shelf' => 'nullable|max:10',
            'position' => 'nullable|integer',
            'is_read' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $book->update($validated);
        
        // Clear cache when updating a book
        cache()->flush();
        
        return response()->json($this->transformCoverUrl($book));
    }

    //Remove the specified resource from storage.
    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $book->delete();
        
        // Clear cache when deleting a book
        cache()->flush();
        
        return response()->json(null, 204);
    }
}
