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
        Schema::create('football_news', function (Blueprint $table) {
            $table->id();
            $table->longText('title');
            $table->longText('body');
            $table->boolean('is_featured_news');
            $table->string('image');
            $table->enum('category', ['Stadiums', 'Leagues', 'Clubs', 'Players', 'International']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_news');
    }
};
