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
        Schema::create('examinees', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('user_id', 30);
            $table->string('name', 20);
            $table->integer('ken')->default(0);
            $table->string('choice', 50)->default("");
            $table->integer('degree')->default(0);
            $table->string('body', 1000)->default("");
            $table->string('secret')->default("");
            $table->integer('is_delete', 1)->default(0);
            $table->string('info');
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
        Schema::dropIfExists('examinees');
    }
};
