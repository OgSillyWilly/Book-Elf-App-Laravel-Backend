<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;

class BookController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        $query = Book::query();

        // Zoeken op titel
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filteren op leesstatus
        if ($request->filled('read_status')) {
            if ($request->read_status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->read_status === 'unread') {
                $query->where('is_read', false);
            }
        }

        $books = $query->orderBy('title')->get();
        return view('books.index', compact('books'));
    }

    // Show the form for creating a new resource.
    public function create()
    {
        return view('books.create');
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {

        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:255',
            'isbn' => 'nullable|max:20',
            'type' => 'required|max:50',
            'publisher' => 'nullable|max:255',
            'publication_date' => 'nullable|date',
            'cover_url' => 'nullable|url|max:2048',
            'cabinet' => 'nullable|max:50',
            'shelf' => 'required|max:10',
            'position' => 'required|integer',
            'is_read' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        Book::create($validated);

        return redirect()->route('books.index')->with('success', 'Boek toegevoegd!');
    }

    // Display the specified resource.
    public function show(string $id)
    {
        //
    }

    // Show the form for editing the specified resource.
    public function edit(string $id)
    {

        $book = Book::findOrFail($id);
        return view('books.edit', compact('book'));
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {

        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:255',
            'isbn' => 'nullable|max:20',
            'type' => 'required|max:50',
            'publisher' => 'nullable|max:255',
            'publication_date' => 'nullable|date',
            'cover_url' => 'nullable|url|max:2048',
            'cabinet' => 'nullable|max:50',
            'shelf' => 'required|max:10',
            'position' => 'required|integer',
            'is_read' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $book->update($validated);

        return redirect()->route('books.index')->with('success', 'Boek bijgewerkt!');
    }

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {

        $book = Book::findOrFail($id);
        $book->delete();

        return redirect()->route('books.index')->with('success', 'Boek verwijderd!');
    }
}
