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
            $table->integer('comments_relevant_loc')->nullable()->after('comments_relevant');
            $table->integer('comments_copyright_loc')->nullable()->after('comments_copyright');
            $table->integer('comments_auxiliary_loc')->nullable()->after('comments_auxiliary');
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
            $table->dropColumn('comments_relevant_loc');
            $table->dropColumn('comments_copyright_loc');
            $table->dropColumn('comments_auxiliary_loc');
        });
    }
};
