<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * registro de recepción del vehículo.
 * Guarda en qué condiciones llegó el vehículo al taller cada vez que se recibe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id('id_recepcion');
            $table->unsignedBigInteger('id_vehiculo');
            $table->foreign('id_vehiculo')->references('id_vehiculo')->on('vehiculos')->onDelete('cascade');
            $table->unsignedInteger('kilometraje')->comment('Kilometraje marcado al recibir el vehículo');
            $table->string('nivel_combustible', 10)->comment('Vacío, 1/4, 1/2, 3/4 o Lleno');
            $table->text('danos_visibles')->nullable()->comment('Golpes, rayones u otros daños visibles al recibirlo');
            $table->text('observaciones')->nullable()->comment('Notas adicionales del mecánico');
            $table->timestamp('fecha_recepcion')->useCurrent()->comment('Se guarda automáticamente al registrar');
            $table->unsignedBigInteger('id_usuario')->nullable()->comment('Usuario que registró la recepción');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->timestamps();

            $table->index(['id_vehiculo', 'fecha_recepcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepciones');
    }
};