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
        Schema::create('repositories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('github_id');
            $table->index('github_id');


            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users');

            $table->string('name');
            $table->string('full_name');

            $table->string('default_branch');
            $table->string('language')->nullable();
            $table->boolean('is_fork')->default(false);
            $table->integer('forks_count')->default(0);

            $table->integer('stargazers_count')->default(0); // stars
            $table->integer('subscribers_count')->default(0); // followers

            $table->string('description')->nullable();
            $table->string('url')->nullable();
            $table->string('html_url')->nullable();

            $table->timestamp('pushed_at')->nullable()->default(null);
            $table->timestamp('last_check')->nullable()->default(null); // for updating the dataset
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repositories');
    }
};
