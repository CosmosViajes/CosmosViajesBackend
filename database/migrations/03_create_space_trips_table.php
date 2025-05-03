<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
{
    Schema::create('space_trips', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('company_id'); // Relación con la tabla users
        $table->string('type');
        $table->string('photo')->nullable();
        $table->dateTime('departure');
        $table->dateTime('duration');
        $table->integer('capacity');
        $table->decimal('price', 8, 2);
        $table->text('description');
        $table->timestamps();
    
        // Clave foránea
        $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
    });        
}


    public function down(): void
    {
        Schema::dropIfExists('space_trips');
    }
};
