<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'type' => fake()->randomElement(['Roman', 'Thriller', 'Fantasy', 'Non-fictie', 'Biografie', 'Strip']),
            'publisher' => fake()->company(),
            'publication_date' => fake()->date(),
            'cover_url' => null,
            'cabinet' => fake()->optional()->numberBetween(1, 5),
            'shelf' => fake()->numberBetween(1, 10),
            'position' => fake()->numberBetween(1, 50),
            'is_read' => fake()->boolean(),
            'start_date' => fake()->optional()->date(),
            'end_date' => fake()->optional()->date(),
            'has_slipcase' => fake()->boolean(20),
            'has_dustjacket' => fake()->boolean(30),
        ];
    }
}
