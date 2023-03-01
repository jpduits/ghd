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
        Schema::create('forks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('github_id');
            $table->index('github_id');

            $table->bigInteger('repository_id')->unsigned();
            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->index('repository_id');

            $table->string('full_name');

            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users');

            $table->timestamp('created_at');

            $table->string('url')->nullable();
            $table->string('html_url')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('forks');
    }
};
