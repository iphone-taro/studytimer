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
        Schema::create('sk_goods', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('place')->default(99);
            $table->integer('deleted')->default(1);
            $table->string('kbn')->default("");
            $table->string('name')->default("");
            $table->string('url')->default("");
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
        Schema::dropIfExists('sk_goods');
    }
};
