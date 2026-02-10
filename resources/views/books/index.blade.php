<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Boekenkast</title>
    @vite('resources/css/books.css')
</head>

<body>
    <h1>Mijn Boekenkast</h1>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="filter-controls">
        <form method="GET" action="{{ route('books.index') }}" style="display: flex; gap: 10px; margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Zoek op titel..." value="{{ request('search') }}" style="flex: 1; padding: 8px;">
            <select name="read_status" style="padding: 8px;">
                <option value="">Alle boeken</option>
                <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
            </select>
            <button type="submit" class="btn btn-primary">Zoeken</button>
            <a href="{{ route('books.index') }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <a href="{{ route('books.create') }}" class="btn btn-success">Nieuw Boek Toevoegen</a>

    <table>
        <thead>
            <tr>
                <th>Cover</th>
                <th>Titel</th>
                <th>Auteur</th>
                <th>Type</th>
                <th>Kast</th>
                <th>Plank</th>
                <th>Positie</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($books as $book)
                <tr>
                    <td>
                        @if($book->cover_url)
                            <img src="{{ $book->cover_url }}" alt="Cover" style="width: 50px; height: auto; border-radius: 4px;">
                        @else
                            <div style="width: 50px; height: 70px; background: #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📚</div>
                        @endif
                    </td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->type }}</td>
                    <td>{{ $book->cabinet ?? '-' }}</td>
                    <td>{{ $book->shelf }}</td>
                    <td>{{ $book->position }}</td>
                    <td>
                        @if($book->is_read)
                            <span style="color: green;">✓ Gelezen</span>
                            @if($book->end_date)
                                <br><small>{{ \Carbon\Carbon::parse($book->end_date)->format('d-m-Y') }}</small>
                            @endif
                        @else
                            <span style="color: gray;">Ongelezen</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('books.edit', $book->id) }}" class="btn btn-warning">Bewerken</a>
                        <form action="{{ route('books.destroy', $book->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Weet je het zeker?')">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
