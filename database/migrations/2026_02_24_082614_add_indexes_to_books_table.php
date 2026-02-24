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
        Schema::table('books', function (Blueprint $table) {
            // Add indexes for frequently searched/filtered columns
            $table->index('title'); // For search
            $table->index('author'); // For search and sorting
            $table->index('isbn'); // For search
            $table->index('type'); // For filtering
            $table->index('is_read'); // For filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['title']);
            $table->dropIndex(['author']);
            $table->dropIndex(['isbn']);
            $table->dropIndex(['type']);
            $table->dropIndex(['is_read']);
        });
    }
};
