<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $books = Book::all();
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
            'shelf' => 'required|max:10',
            'position' => 'required|integer',
            'is_read' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $book = Book::create($validated);
        return response()->json($book, 201);
    }

    // Display the specified resource.
    public function show(string $id)
    {
        $book = Book::findOrFail($id);
        return response()->json($book);
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
            'cabinet' => 'nullable|max:50',
            'shelf' => 'required|max:10',
            'position' => 'required|integer',
            'is_read' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $book->update($validated);
        return response()->json($book);
    }

    //Remove the specified resource from storage.
    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $book->delete();
        return response()->json(null, 204);
    }
}
