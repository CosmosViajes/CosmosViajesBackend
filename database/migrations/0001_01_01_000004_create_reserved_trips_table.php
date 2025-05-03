<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reserved_trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Clave foránea hacia 'users'
            $table->unsignedBigInteger('trip_id'); // Clave foránea hacia 'trips'
            $table->timestamps();

            // Relaciones con las tablas existentes
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('trip_id')->references('id')->on('space_trips')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserved_trips');
    }
};
