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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_set_id')->constrained()->onDelete('cascade');
            $table->integer('board_number');
            $table->enum('vulnerability', ['None', 'NS', 'EW', 'All']);
            $table->json('cards_north');
            $table->json('cards_south');
            $table->json('cards_east');
            $table->json('cards_west');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
