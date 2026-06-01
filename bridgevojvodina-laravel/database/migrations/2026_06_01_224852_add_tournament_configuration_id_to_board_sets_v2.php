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
        if (!Schema::hasColumn("board_sets", "tournament_configuration_id")) {
            Schema::table("board_sets", function (Blueprint $table) {
                $table->foreignUuid("tournament_configuration_id")->after("id")->nullable()->constrained("tournament_configurations")->onDelete("cascade");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("board_sets", function (Blueprint $table) {
            if (Schema::hasColumn("board_sets", "tournament_configuration_id")) {
                $table->dropForeign(["tournament_configuration_id"]);
                $table->dropColumn("tournament_configuration_id");
            }
        });
    }
};
