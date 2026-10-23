<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\BookCoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FetchBookCovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:fetch-covers 
                            {--force : Fetch covers even for books that already have them}
                            {--limit= : Limit the number of books to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch cover images for books that don\'t have them';

    protected $coverService;

    public function __construct(BookCoverService $coverService)
    {
        parent::__construct();
        $this->coverService = $coverService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $limit = $this->option('limit');

        $query = Book::withoutGlobalScopes();

        if (!$force) {
            $query->whereNull('cover_url')
                  ->orWhere('cover_url', '');
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $books = $query->get();
        $total = $books->count();

        if ($total === 0) {
            $this->info('No books found to process.');
            return 0;
        }

        $this->info("Processing {$total} books...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successful = 0;
        $failed = 0;

        foreach ($books as $book) {
            $coverUrl = $this->coverService->fetchCoverUrl(
                $book->isbn,
                $book->title,
                $book->author
            );

            if ($coverUrl) {
                $book->cover_url = $coverUrl;
                $book->save();
                $successful++;
            } else {
                $failed++;
            }

            $bar->advance();
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully fetched covers: {$successful}");
        if ($failed > 0) {
            $this->warn("✗ Failed to fetch covers: {$failed}");
        }

        // Clear books-related cache after updating
        $cacheKeys = Cache::get('books_cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('books_cache_keys');
        $this->info('Cache cleared.');

        return 0;
    }
}
