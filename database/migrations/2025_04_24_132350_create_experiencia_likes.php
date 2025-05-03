<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('experiencia_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('experiencia_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['experiencia_id', 'user_id']);
            $table->foreign('experiencia_id')->references('id')->on('experiencias')->onDelete('cascade');
            // user_id debe ser el id de tu tabla users
        });
    }   

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiencia_likes');
    }
};
