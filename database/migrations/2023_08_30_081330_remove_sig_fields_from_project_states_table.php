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
            $table->dropColumn('volume');
            $table->dropColumn('complexity');
            $table->dropColumn('duplication');
            $table->dropColumn('unit_size');
            $table->dropColumn('maintainability_index');
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
            $table->integer('volume')->nullable();
            $table->float('complexity')->nullable();
            $table->integer('duplication')->nullable();
            $table->integer('unit_size')->nullable();
            $table->integer('maintainability_index')->nullable();
        });
    }
};
