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
        Schema::create('football_clubs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('football_league_id');
            $table->string('name');
            $table->string('logo_primary')->nullable();
            $table->string('logo_white')->nullable();
            $table->double('visit_count')->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->timestamps();

            $table->foreign('football_league_id')->references('id')->on('football_leagues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_clubs');
    }
};
