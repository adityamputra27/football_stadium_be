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
        Schema::create('stadium_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stadium_id');
            $table->string('file');
            $table->string('file_ext');
            $table->string('file_size');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('stadium_id')->references('id')->on('stadiums');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stadium_files');
    }
};
