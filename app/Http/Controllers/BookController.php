<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;

class BookController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        $query = Book::query();

        // Zoeken op titel (with LIKE wildcard escaping)
        if ($request->filled('search')) {
            $search = addcslashes(trim($request->search), '%_');
            $query->where('title', 'like', "%{$search}%");
        }

        // Filteren op leesstatus
        if ($request->filled('read_status')) {
            if ($request->read_status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->read_status === 'unread') {
                $query->where('is_read', false);
            }
        }

        $books = $query->orderBy('title')->paginate(50);
        return view('books.index', compact('books'));
    }

    // Show the form for creating a new resource.
    public function create()
    {
        return view('books.create');
    }

    // Store a newly created resource in storage.
    public function store(StoreBookRequest $request)
    {
        Book::create($request->validated());

        return redirect()->route('books.index')->with('success', 'Boek toegevoegd!');
    }

    // Display the specified resource.
    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }

    // Show the form for editing the specified resource.
    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    // Update the specified resource in storage.
    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());

        return redirect()->route('books.index')->with('success', 'Boek bijgewerkt!');
    }

    // Remove the specified resource from storage.
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('books.index')->with('success', 'Boek verwijderd!');
    }
}
