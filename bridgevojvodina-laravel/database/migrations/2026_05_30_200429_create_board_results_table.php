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
        Schema::create('board_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->foreignId('north_player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('south_player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('east_player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('west_player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->string('contract');
            $table->enum('declarer', ['N', 'S', 'E', 'W']);
            $table->integer('tricks');
            $table->integer('score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_results');
    }
};
