<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
            'cover_url' => ['nullable', 'max:2048', function ($attribute, $value, $fail) {
                // Accept either full URLs or relative paths starting with /
                if ($value && !filter_var($value, FILTER_VALIDATE_URL) && !str_starts_with($value, '/')) {
                    $fail('The ' . $attribute . ' must be a valid URL or relative path.');
                }
            }],
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
            'series_id' => ['nullable', 'integer', Rule::exists('series', 'id')->where('user_id', $this->user()?->id)],
        ];
    }
}
