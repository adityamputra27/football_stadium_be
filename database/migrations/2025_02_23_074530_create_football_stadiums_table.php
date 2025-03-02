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
        Schema::create('football_stadiums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('football_club_id');
            $table->string('name');
            $table->string('capacity');
            $table->string('country');
            $table->string('city');
            $table->string('cost');
            $table->string('status');
            $table->longText('description');
            $table->timestamps();

            $table->foreign('football_club_id')->references('id')->on('football_clubs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_stadiums');
    }
};
