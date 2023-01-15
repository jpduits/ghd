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
            $table->unsignedBigInteger('id');
            $table->primary('id');
            $table->unsignedBigInteger('repository_id');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('committer_id');
            $table->string('message');
            $table->string('sha');
            $table->string('node_id');
            $table->timestamp('created_at');
            $table->string('html_url');

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
