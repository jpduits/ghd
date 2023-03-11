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
        Schema::create('fail_save', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('repository_id')->nullable();
            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->integer('page')->nullable();
            $table->boolean('finished')->default(false);
            $table->enum('parser', ['commits', 'issues', 'pull_requests', 'stargazers', 'forks', 'contributors']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fail_save');
    }
};
