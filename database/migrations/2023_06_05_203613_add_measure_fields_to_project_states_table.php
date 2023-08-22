<?php

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
            $table->string('quadrant')->nullable();
            $table->integer('bugs_current_period')->default(0);
            $table->integer('bugs_total')->default(0);
            $table->integer('bugs_closed_current_period')->default(0);
            $table->integer('bugs_closed_total')->default(0);
            $table->integer('support_current_period')->default(0);
            $table->integer('support_total')->default(0);
            $table->integer('support_closed_current_period')->default(0);
            $table->integer('support_closed_total')->default(0);
            $table->bigInteger('total_loc')->default(0);
            $table->integer('total_kloc')->default(0);
            $table->string('sig_volume_ranking')->nullable();
            $table->integer('sig_volume_ranking_numeric')->nullable();
            $table->bigInteger('loc_complexity_per_risk')->default(0);
            $table->float('percentage_complexity_per_risk', 12, 8)->default(0);
            $table->bigInteger('loc_unit_size_per_risk')->default(0);
            $table->float('percentage_unit_size_per_risk', 12, 8)->default(0);
            $table->string('sig_complexity_ranking')->nullable();
            $table->integer('sig_complexity_ranking_value')->nullable();
            $table->string('sig_unit_size_ranking')->nullable();
            $table->integer('sig_unit_size_ranking_value')->nullable();
            $table->integer('duplication_line_count')->default(0);
            $table->integer('duplication_block_count')->default(0);
            $table->float('duplication_percentage', 12, 8)->default(0);
            $table->string('sig_duplication_ranking')->nullable();
            $table->integer('sig_duplication_ranking_numeric')->nullable();
            $table->string('sig_analysability_ranking')->nullable();
            $table->integer('sig_analysability_ranking_numeric')->nullable();
            $table->string('sig_changeability_ranking')->nullable();
            $table->integer('sig_changeability_ranking_numeric')->nullable();
            $table->string('sig_testability_ranking')->nullable();
            $table->integer('sig_testability_ranking_numeric')->nullable();
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
            $table->dropColumn('quadrant');
            $table->dropColumn('bugs_current_period');
            $table->dropColumn('bugs_total');
            $table->dropColumn('bugs_closed_current_period');
            $table->dropColumn('bugs_closed_total');
            $table->dropColumn('support_current_period');
            $table->dropColumn('support_total');
            $table->dropColumn('support_closed_current_period');
            $table->dropColumn('support_closed_total');
            $table->dropColumn('total_loc');
            $table->dropColumn('total_kloc');
            $table->dropColumn('sig_volume_ranking');
            $table->dropColumn('sig_volume_ranking_numeric');
            $table->dropColumn('loc_complexity_per_risk');
            $table->dropColumn('percentage_complexity_per_risk');
            $table->dropColumn('loc_unit_size_per_risk');
            $table->dropColumn('percentage_unit_size_per_risk');
            $table->dropColumn('sig_complexity_ranking');
            $table->dropColumn('sig_complexity_ranking_value');
            $table->dropColumn('sig_unit_size_ranking');
            $table->dropColumn('sig_unit_size_ranking_value');
            $table->dropColumn('duplication_line_count');
            $table->dropColumn('duplication_block_count');
            $table->dropColumn('duplication_percentage');
            $table->dropColumn('sig_duplication_ranking');
            $table->dropColumn('sig_duplication_ranking_numeric');
            $table->dropColumn('sig_analysability_ranking');
            $table->dropColumn('sig_analysability_ranking_numeric');
            $table->dropColumn('sig_changeability_ranking');
            $table->dropColumn('sig_changeability_ranking_numeric');
            $table->dropColumn('sig_testability_ranking');
            $table->dropColumn('sig_testability_ranking_numeric');
        });
    }
};
