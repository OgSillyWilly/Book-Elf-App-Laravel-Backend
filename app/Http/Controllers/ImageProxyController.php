<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageProxyController extends Controller
{
    // Whitelist of allowed domains for security
    private const ALLOWED_DOMAINS = [
        'covers.openlibrary.org',
        'books.google.com',
        'www.googleapis.com',
    ];

    public function proxy(Request $request)
    {
        $url = $request->query('url');
        
        if (!$url) {
            return response()->json(['error' => 'URL parameter is required'], 400);
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL format'], 400);
        }

        // Parse URL and validate
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl || !isset($parsedUrl['host']) || !isset($parsedUrl['scheme'])) {
            return response()->json(['error' => 'Invalid URL structure'], 400);
        }

        // Only allow HTTP/HTTPS protocols
        if (!in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            return response()->json(['error' => 'Only HTTP/HTTPS protocols allowed'], 403);
        }

        // Check domain whitelist
        if (!in_array($parsedUrl['host'], self::ALLOWED_DOMAINS)) {
            return response()->json(['error' => 'Domain not allowed'], 403);
        }

        // Prevent access to private IP ranges (SSRF protection)
        $ip = gethostbyname($parsedUrl['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return response()->json(['error' => 'Access to internal IPs is not allowed'], 403);
        }

        try {
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch image'], 500);
            }

            // Verify content type is an image
            $contentType = $response->header('Content-Type');
            if (!str_starts_with($contentType, 'image/')) {
                return response()->json(['error' => 'URL does not point to an image'], 400);
            }
            
            return response($response->body())
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'public, max-age=86400');
        } catch (\Exception $e) {
            Log::error('Image proxy error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch image'], 500);
        }
    }
}
