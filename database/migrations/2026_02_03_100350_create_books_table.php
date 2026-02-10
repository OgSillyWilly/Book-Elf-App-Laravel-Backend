<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');           // titel van het boek
            $table->string('author');          // auteur
            $table->string('isbn')->nullable(); // ISBN (optioneel)
            $table->string('shelf');           // plank (bijv. "A1", "B2")
            $table->integer('position');        // positie op de plank
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
