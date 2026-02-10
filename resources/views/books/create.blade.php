<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boek Toevoegen</title>
    @vite('resources/css/books.css')
    @vite('resources/js/books.js')
</head>

<body>
    <h1>Nieuw Boek Toevoegen</h1>

    <form action="{{ route('books.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="search-language">Zoektaal</label>
            <select id="search-language">
                <option value="">Alle talen</option>
                <option value="nl">Nederlands</option>
                <option value="en">Engels</option>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Titel *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required>
            <button type="button" id="search-book" class="btn btn-warning">Zoek</button>
            @error('title')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="author">Auteur *</label>
            <input type="text" id="author" name="author" value="{{ old('author') }}" required>
            <button type="button" id="search-author" class="btn btn-warning">Zoek</button>
            @error('author')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="isbn">ISBN (optioneel)</label>
            <input type="text" id="isbn" name="isbn" value="{{ old('isbn') }}">
            <button type="button" id="search-isbn" class="btn btn-warning">Zoek</button>
            @error('isbn')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="type">Type *</label>
            <select id="type" name="type" required>
                <option value="boek" {{ old('type') == 'boek' ? 'selected' : '' }}>Boek</option>
                <option value="bundel" {{ old('type') == 'bundel' ? 'selected' : '' }}>Bundel</option>
                <option value="novella" {{ old('type') == 'novella' ? 'selected' : '' }}>Novella</option>
                <option value="strip" {{ old('type') == 'strip' ? 'selected' : '' }}>Strip</option>
                <option value="graphic novel" {{ old('type') == 'graphic novel' ? 'selected' : '' }}>Graphic Novel</option>
                <option value="manga" {{ old('type') == 'manga' ? 'selected' : '' }}>Manga</option>
            </select>
            @error('type')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="publisher">Uitgever (optioneel)</label>
            <input type="text" id="publisher" name="publisher" value="{{ old('publisher') }}">
            @error('publisher')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="publication_date">Publicatiedatum (optioneel)</label>
            <input type="date" id="publication_date" name="publication_date" value="{{ old('publication_date') }}">
            @error('publication_date')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="cabinet">Kast (optioneel)</label>
            <input type="text" id="cabinet" name="cabinet" value="{{ old('cabinet') }}">
            @error('cabinet')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="shelf">Plank (bijv. A1, B2) *</label>
            <input type="text" id="shelf" name="shelf" value="{{ old('shelf') }}" required>
            @error('shelf')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="position">Positie op de plank *</label>
            <input type="number" id="position" name="position" value="{{ old('position') }}" required>
            @error('position')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_read" value="1" {{ old('is_read') ? 'checked' : '' }}>
                Gelezen
            </label>
            @error('is_read')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="start_date">Startdatum lezen (optioneel)</label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}">
            @error('start_date')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="end_date">Einddatum lezen (optioneel)</label>
            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}">
            @error('end_date')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-success">Opslaan</button>
        <a href="{{ route('books.index') }}" class="btn btn-danger">Annuleren</a>
    </form>
</body>

</html>
