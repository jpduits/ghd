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
            $table->bigIncrements('id');
            $table->string('run_uuid');

            $table->timestamps();

            $table->unsignedBigInteger('repository_id');
            $table->foreign('repository_id')->references('id')->on('repositories');

            $table->timestamp('period_start_date');
            $table->timestamp('period_end_date');

            $table->timestamp('previous_period_start_date');
            $table->timestamp('previous_period_end_date');

            $table->integer('interval_weeks')->nullable();

            $table->float('sticky_metric_score', 12, 8)->nullable();
            $table->float('magnet_metric_score', 12, 8)->nullable();

            $table->integer('developers_current_period')->nullable();
            $table->integer('developers_new_current_period')->nullable();
            $table->integer('developers_total')->nullable();



            $table->integer('developers_with_contributions_previous_period')->nullable();
            $table->integer('developers_with_contributions_current_period')->nullable();
            $table->integer('developers_with_contributions_previous_and_current_period')->nullable();

            $table->integer('issues_count_current_period')->nullable();
            $table->integer('issues_count_total')->nullable();

            $table->integer('stargazers_count_current_period')->nullable();
            $table->integer('stargazers_count_total')->nullable();

            $table->integer('pull_requests_count_current_period')->nullable();
            $table->integer('pull_requests_count_total')->nullable();

            $table->integer('forks_count_current_period')->nullable();
            $table->integer('forks_count_total')->nullable();

            // do i need these?
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
