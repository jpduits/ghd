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
        Schema::create('project_states', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('rangeStartDate');
            $table->timestamp('rangeEndDate');
            $table->integer('interval')->nullable();
            $table->float('sticky_score')->nullable();
            $table->float('magnet_score')->nullable();
            $table->integer('issues_count')->nullable();
            // do i need these?
            $table->integer('contributors_total')->nullable();
            $table->integer('contributors_new')->nullable();
            $table->integer('contributors_with_contributions_last_period')->nullable();
            $table->integer('contributors_with_contributions_current_period')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_states');
    }
};
