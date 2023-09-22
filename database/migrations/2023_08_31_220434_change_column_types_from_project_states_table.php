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
            $table->text('loc_complexity_per_risk')->change();
            $table->text('percentage_complexity_per_risk')->change();
            $table->text('loc_unit_size_per_risk')->change();
            $table->text('percentage_unit_size_per_risk')->change();
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
            $table->integer('loc_complexity_per_risk')->change();
            $table->integer('percentage_complexity_per_risk')->change();
            $table->integer('loc_unit_size_per_risk')->change();
            $table->integer('percentage_unit_size_per_risk')->change();
        });
    }
};
