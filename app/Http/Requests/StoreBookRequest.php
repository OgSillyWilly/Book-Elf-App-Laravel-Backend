<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'author' => 'required|max:255',
            'isbn' => 'nullable|max:20',
            'type' => 'nullable|max:50',
            'publisher' => 'nullable|max:255',
            'publication_date' => 'nullable|date',
            'cover_url' => 'nullable|url|max:2048',
            'has_slipcase' => 'nullable|boolean',
            'has_dustjacket' => 'nullable|boolean',
            'cabinet' => 'nullable|max:50',
            'shelf' => 'nullable|max:10',
            'position' => 'nullable|integer',
            'is_read' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'year_read' => 'nullable|integer|min:1900|max:2100',
            'rating' => 'nullable|numeric|min:0|max:10',
        ];
    }
}
