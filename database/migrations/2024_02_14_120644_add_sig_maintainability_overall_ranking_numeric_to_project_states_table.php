<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_states', function (Blueprint $table) {
            DB::statement('ALTER TABLE project_states ADD sig_maintainability_overall_ranking_numeric INT AS (ROUND(
                (IFNULL(sig_analysability_ranking_numeric, 0) +
                 IFNULL(sig_changeability_ranking_numeric, 0) +
                 IFNULL(sig_testability_ranking_numeric, 0) +
                 3) / 4)) STORED');
                // 3 is neutral value for stability
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_states', function (Blueprint $table) {
            $table->dropColumn('sig_maintainability_overall_ranking_numeric');
        });
    }
};
