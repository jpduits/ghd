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
        Schema::create('pull_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('github_id');
            $table->index('github_id');

            $table->bigInteger('number')->unsigned()->nullable();
            $table->string('state')->nullable();
            $table->bigInteger('user_id')->unsigned(); // user who created the pull request

            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('merged_at')->nullable();

            $table->string('merge_commit_sha')->nullable();
            // head
            $table->unsignedBigInteger('head_repository_id')->nullable();
            $table->foreign('head_repository_id')->references('id')->on('repositories');
            $table->string('head_ref')->nullable(); // branch name
            $table->string('head_sha')->nullable(); // most recent commit sha (last commit of parent branch)
            $table->bigInteger('head_user_id')->unsigned()->nullable();
            $table->foreign('head_user_id')->references('id')->on('users');
            $table->string('head_full_name')->nullable();


            // base
            $table->unsignedBigInteger('base_repository_id')->nullable();
            $table->foreign('base_repository_id')->references('id')->on('repositories');
            $table->string('base_ref')->nullable(); // branch name
            $table->string('base_sha')->nullable();
            $table->bigInteger('base_user_id')->unsigned()->nullable();
            $table->foreign('base_user_id')->references('id')->on('users');
            $table->string('base_full_name')->nullable();


            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('url');
            $table->string('html_url');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pull_requests');
    }
};
