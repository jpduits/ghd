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
            $table->float('issues_average_duration_days', 12, 8)->nullable();
            $table->integer('comments_total')->nullable();
            $table->integer('comments_relevant_percentage')->nullable();
            $table->integer('comments_relevant')->nullable();
            $table->integer('comments_copyright')->nullable();
            $table->integer('comments_auxiliary')->nullable();

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
            $table->dropColumn('issues_average_duration_days');
            $table->dropColumn('comments_total');
            $table->dropColumn('comments_relevant_percentage');
            $table->dropColumn('comments_relevant');
            $table->dropColumn('comments_copyright');
            $table->dropColumn('comments_auxiliary');
        });
    }
};
