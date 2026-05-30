<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to match the new enum values
        DB::table("clubs")->where("status", "Aktivan")->update(["status" => "Active"]);
        DB::table("clubs")->where("status", "Neaktivan")->update(["status" => "Inactive"]);
        
        // Ensure any other values are also set to a default to avoid migration failure if strictly enforced
        DB::table("clubs")->whereNotIn("status", ["Active", "Inactive"])->update(["status" => "Active"]);

        Schema::table("clubs", function (Blueprint $table) {
            $table->enum("status", ["Active", "Inactive"])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("clubs", function (Blueprint $table) {
            $table->string("status")->change();
        });

        // Optionally map back
        DB::table("clubs")->where("status", "Active")->update(["status" => "Aktivan"]);
        DB::table("clubs")->where("status", "Inactive")->update(["status" => "Neaktivan"]);
    }
};
