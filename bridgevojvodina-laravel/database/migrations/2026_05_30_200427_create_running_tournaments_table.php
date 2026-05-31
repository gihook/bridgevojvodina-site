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
        Schema::create('running_tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->json('team_results')->nullable();
            $table->foreignUuid('tournament_id')->nullable()->constrained('tournaments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_tournaments');
    }
};
