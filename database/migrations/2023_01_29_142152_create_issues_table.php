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
        Schema::create('issues', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('github_id');
            $table->index('github_id');

            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->bigInteger('repository_id')->unsigned();
            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->index('repository_id');

            $table->string('title')->nullable();
            $table->bigInteger('number')->unsigned()->nullable();
            $table->string('state')->nullable();

            $table->bigInteger('comments')->unsigned()->default(0);

            $table->string('url')->nullable();
            $table->string('html_url')->nullable();

            $table->timestamps();
            $table->timestamp('closed_at')->nullable();

            $table->longText('body')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues');
    }
};
