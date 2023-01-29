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
            $table->unsignedBigInteger('id');
            $table->primary('id');
            $table->string('node_id');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('repository_id')->unsigned();
            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('state')->nullable();
            $table->bigInteger('number')->unsigned()->nullable();
            $table->bigInteger('comments')->unsigned()->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('issues');
    }
};
