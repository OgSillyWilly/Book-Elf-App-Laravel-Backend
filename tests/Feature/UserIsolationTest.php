<?php

use App\Models\Book;
use App\Models\ReadingChallenge;
use App\Models\Series;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Cache::flush();
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();

    $this->aliceBook = Book::factory()->for($this->alice)->create(['title' => 'Alice Book', 'is_read' => true, 'type' => 'hardback', 'rating' => 9, 'year_read' => (int) date('Y'), 'end_date' => now()->toDateString()]);
    $this->bobBook = Book::factory()->for($this->bob)->create(['title' => 'Bob Book', 'is_read' => true, 'type' => 'hardback', 'rating' => 5, 'year_read' => (int) date('Y'), 'end_date' => now()->toDateString()]);
    $this->bobSeries = Series::factory()->for($this->bob)->create(['name' => 'Bob Series']);
    $this->bobChallenge = ReadingChallenge::factory()->for($this->bob)->create(['name' => 'Bob Challenge']);
});

dataset('protected endpoints', [
    ['GET', '/api/books'],
    ['POST', '/api/books'],
    ['GET', '/api/books/1'],
    ['PUT', '/api/books/1'],
    ['DELETE', '/api/books/1'],
    ['POST', '/api/books/1/cover'],
    ['DELETE', '/api/books/1/cover'],
    ['POST', '/api/books/import'],
    ['GET', '/api/books/template/download'],
    ['GET', '/api/books/reading-history'],
    ['GET', '/api/series'],
    ['POST', '/api/series'],
    ['GET', '/api/series/1'],
    ['DELETE', '/api/series/1'],
    ['GET', '/api/reading-challenges'],
    ['GET', '/api/reading-challenges/active'],
    ['GET', '/api/reading-challenges/1'],
    ['GET', '/api/reading-challenges/1/suggestions'],
    ['DELETE', '/api/reading-challenges/1'],
    ['GET', '/api/statistics/overview'],
    ['GET', '/api/statistics/by-type'],
    ['GET', '/api/statistics/top-rated'],
    ['GET', '/api/statistics/yearly-history'],
    ['GET', '/api/statistics/series-progress'],
    ['GET', '/api/statistics/reading-patterns'],
    ['GET', '/api/statistics/author-progress?author=x'],
    ['POST', '/api/statistics/clear-cache'],
    ['GET', '/api/image-proxy?url=x'],
]);

it('rejects unauthenticated requests with 401', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with('protected endpoints');

it('keeps register and login public', function () {
    postJson('/api/login', [])->assertStatus(422);
    postJson('/api/register', [])->assertStatus(422);
});

describe('books', function () {
    beforeEach(fn () => Sanctum::actingAs($this->alice));

    it('lists only own books', function () {
        getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Alice Book');
    });

    it('does not share the list cache between users', function () {
        getJson('/api/books')->assertJsonPath('data.0.title', 'Alice Book');
        Sanctum::actingAs($this->bob);
        getJson('/api/books')->assertJsonPath('data.0.title', 'Bob Book');
    });

    it('returns 404 when showing, updating or deleting another user\'s book', function () {
        getJson("/api/books/{$this->bobBook->id}")->assertNotFound();
        putJson("/api/books/{$this->bobBook->id}", ['title' => 'x', 'author' => 'y'])->assertNotFound();
        patchJson("/api/books/{$this->bobBook->id}", ['title' => 'x', 'author' => 'y'])->assertNotFound();
        deleteJson("/api/books/{$this->bobBook->id}")->assertNotFound();

        expect($this->bobBook->fresh()->title)->toBe('Bob Book');
    });

    it('assigns new books to the current user and ignores a posted user_id', function () {
        $id = postJson('/api/books', ['title' => 'New', 'author' => 'A', 'user_id' => $this->bob->id])
            ->assertCreated()->json('id');

        expect(Book::withoutGlobalScopes()->find($id)->user_id)->toBe($this->alice->id);
    });

    it('rejects a series_id belonging to another user', function () {
        postJson('/api/books', ['title' => 'New', 'author' => 'A', 'series_id' => $this->bobSeries->id])
            ->assertStatus(422)->assertJsonValidationErrors('series_id');

        putJson("/api/books/{$this->aliceBook->id}", ['title' => 'x', 'author' => 'y', 'series_id' => $this->bobSeries->id])
            ->assertStatus(422)->assertJsonValidationErrors('series_id');
    });

    it('accepts an own series_id', function () {
        $series = Series::factory()->for($this->alice)->create();
        postJson('/api/books', ['title' => 'New', 'author' => 'A', 'series_id' => $series->id])
            ->assertCreated()->assertJsonPath('series_id', $series->id);
    });

    it('only includes own books in reading history', function () {
        getJson('/api/books/reading-history')->assertOk()->assertJsonPath('0.count', 1);
    });
});

describe('covers', function () {
    beforeEach(function () {
        Storage::fake('public');
        Sanctum::actingAs($this->alice);
    });

    it('stores covers in a per-user directory', function () {
        postJson("/api/books/{$this->aliceBook->id}/cover", ['cover' => UploadedFile::fake()->image('c.jpg')])
            ->assertOk();

        $path = str_replace('/storage/', '', strtok($this->aliceBook->fresh()->cover_url, '?'));
        expect($path)->toStartWith("covers/{$this->alice->id}/");
        Storage::disk('public')->assertExists($path);
    });

    it('cannot upload or delete a cover for another user\'s book', function () {
        Storage::disk('public')->put("covers/{$this->bob->id}/bob.jpg", 'x');
        $this->bobBook->update(['cover_url' => "/storage/covers/{$this->bob->id}/bob.jpg"]);

        postJson("/api/books/{$this->bobBook->id}/cover", ['cover' => UploadedFile::fake()->image('c.jpg')])->assertNotFound();
        deleteJson("/api/books/{$this->bobBook->id}/cover")->assertNotFound();

        Storage::disk('public')->assertExists("covers/{$this->bob->id}/bob.jpg");
        expect($this->bobBook->fresh()->cover_url)->toContain('bob.jpg');
    });

    it('deletes only the stored file of an own book', function () {
        postJson("/api/books/{$this->aliceBook->id}/cover", ['cover' => UploadedFile::fake()->image('c.jpg')])->assertOk();
        $path = str_replace('/storage/', '', strtok($this->aliceBook->fresh()->cover_url, '?'));

        deleteJson("/api/books/{$this->aliceBook->id}/cover")->assertOk();

        Storage::disk('public')->assertMissing($path);
        expect($this->aliceBook->fresh()->cover_url)->toBeNull();
    });
});

describe('series', function () {
    beforeEach(fn () => Sanctum::actingAs($this->alice));

    it('lists only own series', function () {
        Series::factory()->for($this->alice)->create(['name' => 'Alice Series']);
        getJson('/api/series')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Alice Series');
    });

    it('returns 404 for another user\'s series', function () {
        getJson("/api/series/{$this->bobSeries->id}")->assertNotFound();
        putJson("/api/series/{$this->bobSeries->id}", ['name' => 'x'])->assertNotFound();
        deleteJson("/api/series/{$this->bobSeries->id}")->assertNotFound();
        expect(Series::withoutGlobalScopes()->find($this->bobSeries->id))->not->toBeNull();
    });

    it('assigns new series to the current user', function () {
        $id = postJson('/api/series', ['name' => 'Mine', 'user_id' => $this->bob->id])->assertCreated()->json('id');
        expect(Series::withoutGlobalScopes()->find($id)->user_id)->toBe($this->alice->id);
    });
});

describe('reading challenges', function () {
    beforeEach(fn () => Sanctum::actingAs($this->alice));

    $payload = fn () => [
        'name' => 'x', 'goal_books' => 5, 'period_type' => 'yearly',
        'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(),
    ];

    it('lists only own challenges and counts only own books', function () {
        $mine = ReadingChallenge::factory()->for($this->alice)->create();

        getJson('/api/reading-challenges')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $mine->id)->assertJsonPath('0.books_read', 1);
    });

    it('returns 404 for another user\'s challenge', function () use ($payload) {
        getJson("/api/reading-challenges/{$this->bobChallenge->id}")->assertNotFound();
        getJson("/api/reading-challenges/{$this->bobChallenge->id}/suggestions")->assertNotFound();
        putJson("/api/reading-challenges/{$this->bobChallenge->id}", $payload())->assertNotFound();
        deleteJson("/api/reading-challenges/{$this->bobChallenge->id}")->assertNotFound();
    });

    it('does not expose another user\'s active challenge', function () {
        getJson('/api/reading-challenges/active')->assertNotFound();
    });

    it('assigns new challenges to the current user', function () use ($payload) {
        $id = postJson('/api/reading-challenges', $payload() + ['user_id' => $this->bob->id])->assertCreated()->json('id');
        expect(ReadingChallenge::withoutGlobalScopes()->find($id)->user_id)->toBe($this->alice->id);
    });

    it('only suggests own unread books', function () {
        $mine = ReadingChallenge::factory()->for($this->alice)->create();
        Book::factory()->for($this->alice)->create(['title' => 'Alice Unread', 'is_read' => false]);
        Book::factory()->for($this->bob)->create(['title' => 'Bob Unread', 'is_read' => false]);

        getJson("/api/reading-challenges/{$mine->id}/suggestions")
            ->assertJsonFragment(['title' => 'Alice Unread'])
            ->assertJsonMissing(['title' => 'Bob Unread']);
    });
});

describe('statistics', function () {
    it('computes and caches per user', function () {
        Book::factory()->for($this->bob)->create(['is_read' => false]);

        Sanctum::actingAs($this->alice);
        getJson('/api/statistics/overview')->assertOk()->assertJsonPath('total_books', 1);

        Sanctum::actingAs($this->bob);
        getJson('/api/statistics/overview')->assertOk()->assertJsonPath('total_books', 2);
    });

    it('scopes top rated, series progress and author progress', function () {
        Sanctum::actingAs($this->alice);

        getJson('/api/statistics/top-rated')->assertJsonCount(1)->assertJsonMissing(['title' => 'Bob Book']);
        getJson('/api/statistics/series-progress')->assertJsonPath('total_series', 0);
        getJson('/api/statistics/author-progress?author=' . urlencode($this->bobBook->author))->assertJsonPath('total_books', 0);
    });

    it('clears only the caller\'s cache', function () {
        Sanctum::actingAs($this->bob);
        getJson('/api/statistics/overview')->assertJsonPath('total_books', 1);
        Sanctum::actingAs($this->alice);
        getJson('/api/statistics/overview')->assertJsonPath('total_books', 1);

        Book::factory()->for($this->bob)->create();
        Book::factory()->for($this->alice)->create();

        postJson('/api/statistics/clear-cache')->assertOk();
        getJson('/api/statistics/overview')->assertJsonPath('total_books', 2);

        Sanctum::actingAs($this->bob);
        getJson('/api/statistics/overview')->assertJsonPath('total_books', 1); // still cached
    });
});

describe('import', function () {
    it('assigns imported books to the current user and keeps them private', function () {
        Sanctum::actingAs($this->alice);
        $csv = UploadedFile::fake()->createWithContent('books.csv', "titel,auteur\nImported,Someone\n");

        postJson('/api/books/import', ['file' => $csv])->assertOk();

        $imported = Book::withoutGlobalScopes()->where('title', 'Imported')->first();
        expect($imported->user_id)->toBe($this->alice->id);

        Sanctum::actingAs($this->bob);
        getJson('/api/books')->assertJsonMissing(['title' => 'Imported']);
    });

    it('clears only the importer\'s list cache', function () {
        Sanctum::actingAs($this->bob);
        getJson('/api/books')->assertJsonCount(1, 'data');
        Sanctum::actingAs($this->alice);
        getJson('/api/books')->assertJsonCount(1, 'data');

        $csv = UploadedFile::fake()->createWithContent('books.csv', "titel,auteur\nImported,Someone\n");
        postJson('/api/books/import', ['file' => $csv])->assertOk();

        getJson('/api/books')->assertJsonCount(2, 'data');
    });
});
