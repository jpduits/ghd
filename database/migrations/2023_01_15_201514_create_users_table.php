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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('github_id')->nullable();
            $table->index('github_id');

            $table->string('login')->nullable();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
            $table->string('url')->nullable();
            $table->string('html_url')->nullable();
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
        Schema::dropIfExists('users');
    }
};
