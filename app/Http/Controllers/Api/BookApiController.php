<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\BookCoverService;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookApiController extends Controller
{
    protected $coverService;

    // Whitelist of allowed sort columns (SQL injection protection)
    private const ALLOWED_SORT_COLUMNS = [
        'title',
        'author',
        'created_at',
        'updated_at',
        'year_read',
        'rating',
        'publication_date',
    ];

    public function __construct(BookCoverService $coverService)
    {
        $this->coverService = $coverService;
    }
    private function transformCoverUrl(?Book $book): ?Book
    {
        if (!$book || !$book->cover_url) {
            return $book;
        }

        // Validate URL and protocol
        if (filter_var($book->cover_url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($book->cover_url);
            // Only proxy HTTP/HTTPS URLs
            if (isset($parsedUrl['scheme']) && in_array($parsedUrl['scheme'], ['http', 'https'])) {
                $book->cover_url = url('/api/image-proxy?url=' . urlencode($book->cover_url));
            }
        }
        
        return $book;
    }

    /**
     * Clear books-related cache
     */
    private function clearBooksCache(): void
    {
        // Clear only books-related cache keys
        $keys = Cache::get('books_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('books_cache_keys');
    }

    // Display a listing of the resource.
    public function index(Request $request)
    {
        // Validate inputs
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'is_read' => 'nullable|boolean',
            'year_read' => 'nullable|integer|min:1900|max:2100',
            'sort_by' => 'nullable|string|in:' . implode(',', self::ALLOWED_SORT_COLUMNS),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Cache key based on normalized query parameters
        $cacheParams = [
            'search' => $request->input('search'),
            'type' => $request->input('type'),
            'is_read' => $request->input('is_read'),
            'year_read' => $request->input('year_read'),
            'sort_by' => $request->input('sort_by', 'title'),
            'per_page' => $request->input('per_page', 50),
            'page' => $request->input('page', 1),
        ];
        $cacheKey = 'books_' . md5(json_encode($cacheParams));
        
        // Track cache keys for targeted invalidation
        $cacheKeys = Cache::get('books_cache_keys', []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put('books_cache_keys', $cacheKeys, 3600);
        }
        
        // Cache for 5 minutes
        $result = Cache::remember($cacheKey, 300, function () use ($request, $cacheParams) {
            $query = Book::query();

            // Search with LIKE wildcard escaping
            if ($request->filled('search')) {
                $search = addcslashes(trim($request->input('search')), '%_');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%");
                });
            }

            // Filter by type
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // Filter by read status
            if ($request->filled('is_read')) {
                $query->where('is_read', filter_var($request->input('is_read'), FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by year read
            if ($request->filled('year_read')) {
                $year = (int)$request->input('year_read');
                if ($year >= 1900 && $year <= 2100) {
                    $query->where('year_read', $year);
                }
            }

            // Sort with whitelist validation
            $sortBy = in_array($request->input('sort_by'), self::ALLOWED_SORT_COLUMNS) 
                ? $request->input('sort_by') 
                : 'title';
            $query->orderBy($sortBy);

            // Paginate results
            $perPage = min((int)$request->input('per_page', 50), 100);
            return $query->paginate($perPage);
        });
        
        // Transform cover URLs
        $result->getCollection()->transform(function ($book) {
            return $this->transformCoverUrl($book);
        });
        
        return response()->json($result);
    }

    // Store a newly created resource in storage.
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        // Auto-fetch cover if not provided
        if (empty($validated['cover_url'])) {
            $coverUrl = $this->coverService->fetchCoverUrl(
                $validated['isbn'] ?? null,
                $validated['title'] ?? null,
                $validated['author'] ?? null
            );
            
            if ($coverUrl) {
                $validated['cover_url'] = $coverUrl;
            }
        }

        $book = Book::create($validated);
        
        // Clear books-related cache
        $this->clearBooksCache();
        
        return response()->json($this->transformCoverUrl($book), 201);
    }

    // Display the specified resource.
    public function show(Book $book)
    {
        return response()->json($this->transformCoverUrl($book));
    }

    //Update the specified resource in storage.
    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();

        // Auto-fetch cover if not provided and book doesn't have one
        if (empty($validated['cover_url']) && empty($book->cover_url)) {
            $coverUrl = $this->coverService->fetchCoverUrl(
                $validated['isbn'] ?? $book->isbn,
                $validated['title'] ?? $book->title,
                $validated['author'] ?? $book->author
            );
            
            if ($coverUrl) {
                $validated['cover_url'] = $coverUrl;
            }
        }

        $book->update($validated);
        
        // Clear books-related cache
        $this->clearBooksCache();
        
        return response()->json($this->transformCoverUrl($book));
    }

    //Remove the specified resource from storage.
    public function destroy(Book $book)
    {
        $book->delete();
        
        // Clear books-related cache
        $this->clearBooksCache();
        
        return response()->json(null, 204);
    }

    // Get reading history grouped by year
    public function readingHistory()
    {
        $history = Book::whereNotNull('year_read')
            ->where('is_read', true)
            ->selectRaw('year_read, COUNT(*) as count')
            ->groupBy('year_read')
            ->orderBy('year_read', 'desc')
            ->get();
        
        return response()->json($history);
    }
}
