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
        Schema::create('reports', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('code', 30);
            $table->string('kbn', 1)->default(0);
            $table->string('title', 200)->default("");
            $table->integer('frame_index')->default(0);
            $table->integer('study_time')->default(0);
            $table->integer('is_access')->default(0);
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
        Schema::dropIfExists('reports');
    }
};
