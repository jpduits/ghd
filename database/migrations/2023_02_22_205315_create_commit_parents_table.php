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
        Schema::create('commit_parents', function (Blueprint $table) {
            $table->primary(['commit_id', 'parent_id']);
            $table->unsignedBigInteger('commit_id');
            $table->unsignedBigInteger('parent_id');
            $table->foreign('commit_id')->references('id')->on('commits');
            $table->foreign('parent_id')->references('id')->on('commits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commit_parents');
    }
};
