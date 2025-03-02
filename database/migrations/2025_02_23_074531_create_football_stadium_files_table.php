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
        Schema::create('football_stadium_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('football_stadium_id');
            $table->string('file')->nullable();
            $table->string('file_ext')->nullable();
            $table->string('file_size')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('football_stadium_id')->references('id')->on('football_stadiums');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_stadium_files');
    }
};
