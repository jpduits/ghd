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
            $table->unsignedBigInteger('id');
            $table->primary('id');
            $table->string('node_id');
            $table->string('name');
            $table->string('full_name');
            $table->unsignedBigInteger('owner_id');
            $table->string('description')->nullable();
            $table->string('html_url')->nullable();
            $table->string('default_branch');
            $table->timestamp('last_check')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users');
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
