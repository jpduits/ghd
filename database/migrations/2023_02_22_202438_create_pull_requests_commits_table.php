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
        Schema::create('pull_requests_commits', function (Blueprint $table) {
            $table->primary(['pull_request_id', 'commit_id']);
            $table->unsignedBigInteger('pull_request_id');
            $table->unsignedBigInteger('commit_id');
            $table->foreign('pull_request_id')->references('github_id')->on('pull_requests');
            $table->foreign('commit_id')->references('id')->on('commits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pull_requests_commits');
    }
};
