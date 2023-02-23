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
        Schema::create('commits', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('repository_id');
            $table->index('repository_id');

            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('committer_id')->nullable();

            $table->string('sha');
            $table->timestamp('created_at');

            $table->text('message')->nullable();
            $table->string('url')->nullable();
            $table->string('html_url')->nullable();

            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->foreign('author_id')->references('id')->on('users');
            $table->foreign('committer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commits');
    }
};
