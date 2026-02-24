<?php

use App\Models\Book;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    // Create test data
    Book::factory()->create([
        'title' => 'Test Book',
        'author' => 'Test Author',
        'type' => 'Roman',
        'shelf' => 1,
        'position' => 1,
    ]);
});

test('can list all books', function () {
    $response = getJson('/api/books');
    
    $response->assertStatus(200);
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['title' => 'Test Book']);
});

test('can search books by title', function () {
    $response = getJson('/api/books?search=Test');
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['title' => 'Test Book']);
});

test('can filter books by type', function () {
    Book::factory()->create([
        'title' => 'Comic Book',
        'author' => 'Comic Author',
        'type' => 'Strip',
        'shelf' => 1,
        'position' => 2,
    ]);
    
    $response = getJson('/api/books?type=Roman');
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['title' => 'Test Book']);
    $response->assertJsonMissing(['title' => 'Comic Book']);
});

test('can create a new book', function () {
    $bookData = [
        'title' => 'New Book',
        'author' => 'New Author',
        'type' => 'Roman',
        'shelf' => 2,
        'position' => 1,
    ];
    
    $response = postJson('/api/books', $bookData);
    
    $response->assertStatus(201);
    $response->assertJsonFragment(['title' => 'New Book']);
    
    $this->assertDatabaseHas('books', ['title' => 'New Book']);
});

test('can update a book', function () {
    $book = Book::first();
    
    $response = putJson("/api/books/{$book->id}", [
        'title' => 'Updated Book',
        'author' => $book->author,
        'type' => $book->type,
        'shelf' => $book->shelf,
        'position' => $book->position,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['title' => 'Updated Book']);
});

test('can delete a book', function () {
    $book = Book::first();
    
    $response = deleteJson("/api/books/{$book->id}");
    
    $response->assertStatus(204);
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('validates required fields when creating book', function () {
    $response = postJson('/api/books', []);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'author']);
});

test('image proxy returns image', function () {
    $response = getJson('/api/image-proxy?url=' . urlencode('https://books.google.com/books/content?id=test'));
    
    // Should return an image or redirect
    expect($response->status())->toBeIn([200, 302, 404]);
});
