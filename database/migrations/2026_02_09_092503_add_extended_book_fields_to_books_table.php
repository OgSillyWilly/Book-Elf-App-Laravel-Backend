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
            $table->string('type')->default('boek')->after('isbn');
            $table->string('publisher')->nullable()->after('type');
            $table->date('publication_date')->nullable()->after('publisher');
            $table->boolean('is_read')->default(false)->after('position');
            $table->date('start_date')->nullable()->after('is_read');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['type', 'publisher', 'publication_date', 'is_read', 'start_date', 'end_date']);
        });
    }
};