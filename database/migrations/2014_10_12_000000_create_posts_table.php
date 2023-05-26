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
        Schema::create('posts', function (Blueprint $table) {
            $table->integer('post_id')->autoIncrement();
            $table->string('game_id', 20);
            $table->string('user_id', 20);
            $table->integer('model')->default(0);
            $table->integer('type')->default(0);
            $table->string('main_id_type', 20);
            $table->string('main_id_label', 20)->default('');
            $table->string('main_id', 40);
            $table->string('sub_id_type', 20);
            $table->string('sub_id_label', 20)->default('');
            $table->string('sub_id', 40)->default('');
            $table->string('comment', 300)->default('');
            $table->string('delete_no', 4)->default('');
            $table->string('user_info', 1000)->default('');
            $table->string('ip_address')->default('');
            $table->timestamp('limit_date');
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
        Schema::dropIfExists('posts');
    }
};
