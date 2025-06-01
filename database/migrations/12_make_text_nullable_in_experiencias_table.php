<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // En el archivo de migración generado
    public function up()
    {
        Schema::table('experiencias', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('experiencias', function (Blueprint $table) {
            $table->text('text')->nullable(false)->change(); // Revertir si es necesario
        });
    }
};
