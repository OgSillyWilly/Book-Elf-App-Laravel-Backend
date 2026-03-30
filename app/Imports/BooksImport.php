<?php

namespace App\Imports;

use App\Models\Book;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BooksImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        // Get title and author from either Dutch or English columns
        $title = $row['titel'] ?? $row['title'] ?? null;
        $author = $row['auteur'] ?? $row['author'] ?? null;

        // Skip rows without title or author
        if (empty($title) || empty($author)) {
            return null;
        }

        return new Book([
            'title'            => $title,
            'author'           => $author,
            'isbn'             => $row['isbn'] ?? null,
            'type'             => $row['type'] ?? 'boek',
            'publisher'        => $row['uitgever'] ?? $row['publisher'] ?? null,
            'publication_date' => $row['publicatiedatum'] ?? $row['publication_date'] ?? null,
            'cabinet'          => $row['kast'] ?? $row['cabinet'] ?? null,
            'shelf'            => $row['plank'] ?? $row['shelf'] ?? null,
            'position'         => $row['positie'] ?? $row['position'] ?? null,
            'is_read'          => $row['gelezen'] ?? $row['is_read'] ?? false,
            'has_slipcase'     => $row['slipcase'] ?? $row['has_slipcase'] ?? false,
            'has_dustjacket'   => $row['dustjacket'] ?? $row['has_dustjacket'] ?? false,
            'start_date'       => $row['startdatum'] ?? $row['start_date'] ?? null,
            'end_date'         => $row['einddatum'] ?? $row['end_date'] ?? null,
            'year_read'        => $row['jaar_gelezen'] ?? $row['year_read'] ?? null,
            'rating'           => $row['beoordeling'] ?? $row['rating'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            // No validation rules - we handle this in model() method
        ];
    }
}
