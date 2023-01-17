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
            $table->unsignedBigInteger('id');
            $table->primary('id');
            $table->string('login');
            $table->string('node_id');
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
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
