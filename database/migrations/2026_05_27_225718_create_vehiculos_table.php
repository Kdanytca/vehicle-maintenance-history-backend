<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id('id_vehiculo');
            $table->string('placa', 15)->unique()->comment('Número de placa único del vehículo');
            $table->string('marca', 50)->comment('Marca del fabricante');
            $table->string('modelo', 50)->comment('Modelo específico del automóvil');
            $table->integer('anio')->nullable()->comment('Año de fabricación del vehículo');
            
            // Llave foránea limpia sin comentarios
            $table->unsignedBigInteger('id_propietario')->nullable();
            $table->foreign('id_propietario')->references('id_propietario')->on('propietarios')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};