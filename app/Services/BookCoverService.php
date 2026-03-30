<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookCoverService
{
    /**
     * Fetch cover URL for a book based on ISBN, title, and author
     * 
     * @param string|null $isbn
     * @param string|null $title
     * @param string|null $author
     * @return string|null
     */
    public function fetchCoverUrl(?string $isbn, ?string $title = null, ?string $author = null): ?string
    {
        // Try Google Books API first (best quality covers)
        if ($isbn) {
            $coverUrl = $this->fetchFromGoogleBooks($isbn);
            if ($coverUrl) {
                return $coverUrl;
            }
        }

        // Try Open Library API as fallback
        if ($isbn) {
            $coverUrl = $this->fetchFromOpenLibrary($isbn);
            if ($coverUrl) {
                return $coverUrl;
            }
        }

        // If ISBN didn't work, try with title and author
        if ($title) {
            $coverUrl = $this->fetchFromGoogleBooksByTitle($title, $author);
            if ($coverUrl) {
                return $coverUrl;
            }
        }

        return null;
    }

    /**
     * Fetch cover from Google Books API by ISBN
     * 
     * @param string $isbn
     * @return string|null
     */
    private function fetchFromGoogleBooks(string $isbn): ?string
    {
        try {
            $response = Http::timeout(5)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:' . $isbn,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['items'][0]['volumeInfo']['imageLinks'])) {
                    $imageLinks = $data['items'][0]['volumeInfo']['imageLinks'];
                    
                    // Prefer larger images
                    if (isset($imageLinks['large'])) {
                        return $imageLinks['large'];
                    } elseif (isset($imageLinks['medium'])) {
                        return $imageLinks['medium'];
                    } elseif (isset($imageLinks['thumbnail'])) {
                        // Convert thumbnail to larger version
                        return str_replace('zoom=1', 'zoom=2', $imageLinks['thumbnail']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Google Books API error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch cover from Google Books API by title and author
     * 
     * @param string $title
     * @param string|null $author
     * @return string|null
     */
    private function fetchFromGoogleBooksByTitle(string $title, ?string $author = null): ?string
    {
        try {
            $query = $title;
            if ($author) {
                $query .= '+inauthor:' . $author;
            }

            $response = Http::timeout(5)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => $query,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['items'][0]['volumeInfo']['imageLinks'])) {
                    $imageLinks = $data['items'][0]['volumeInfo']['imageLinks'];
                    
                    if (isset($imageLinks['large'])) {
                        return $imageLinks['large'];
                    } elseif (isset($imageLinks['medium'])) {
                        return $imageLinks['medium'];
                    } elseif (isset($imageLinks['thumbnail'])) {
                        return str_replace('zoom=1', 'zoom=2', $imageLinks['thumbnail']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Google Books API error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch cover from Open Library API by ISBN
     * 
     * @param string $isbn
     * @return string|null
     */
    private function fetchFromOpenLibrary(string $isbn): ?string
    {
        try {
            // Clean ISBN (remove hyphens)
            $cleanIsbn = str_replace('-', '', $isbn);
            
            // Check if cover exists - Open Library returns a placeholder image if not found
            $response = Http::timeout(5)->get("https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-L.jpg");
            
            // Check if it's a valid response and not too small (placeholder is usually small)
            if ($response->successful() && strlen($response->body()) > 1000) {
                return "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-L.jpg";
            }
        } catch (\Exception $e) {
            Log::warning('Open Library API error: ' . $e->getMessage());
        }

        return null;
    }
}
