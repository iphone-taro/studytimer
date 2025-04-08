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
        Schema::create('shiku_users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('user_id')->default("");
            $table->string('info')->default("");
            $table->integer('guest')->default(1);
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
        Schema::dropIfExists('shiku_users');
    }
};
