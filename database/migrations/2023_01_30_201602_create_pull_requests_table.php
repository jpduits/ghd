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
            $table->unsignedBigInteger('id');
            $table->primary('id');
            $table->string('node_id');
            $table->string('url');
            $table->string('state')->nullable();
            $table->bigInteger('number')->unsigned()->nullable();
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('merge_commit_sha')->nullable();
            $table->unsignedBigInteger('merge_commit_id')->nullable();
            $table->foreign('merge_commit_id')->references('id')->on('commits');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();
            $table->bigInteger('head_user_id')->unsigned()->nullable();
            $table->foreign('head_user_id')->references('id')->on('users');
            $table->unsignedBigInteger('head_repository_id')->nullable();
            $table->foreign('head_repository_id')->references('id')->on('repositories');
            $table->string('head_ref')->nullable();
            $table->bigInteger('base_user_id')->unsigned()->nullable();
            $table->foreign('base_user_id')->references('id')->on('users');
            $table->unsignedBigInteger('base_repository_id')->nullable();
            $table->foreign('base_repository_id')->references('id')->on('repositories');
            $table->string('base_ref')->nullable();
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
