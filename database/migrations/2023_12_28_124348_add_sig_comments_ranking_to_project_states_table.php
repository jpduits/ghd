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
            $table->integer('comments_percentage')->nullable()->after('comments_loc');
            $table->string('sig_comments_ranking')->nullable();
            $table->integer('sig_comments_ranking_numeric')->nullable();
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
            $table->dropColumn('comments_percentage');
            $table->dropColumn('sig_comments_ranking');
            $table->dropColumn('sig_comments_ranking_numeric');
        });
    }

};
