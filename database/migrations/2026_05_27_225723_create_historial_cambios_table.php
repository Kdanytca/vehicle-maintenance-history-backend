<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->string('tipo_evento', 50)->comment('Categoría: LOGIN_FALLIDO, AUDITORIA_CAMBIO, LOG_SISTEMA');
            $table->text('descripcion_evento')->comment('Detalle del cambio realizado o del fallo detectado');
            $table->string('direccion_ip', 45)->nullable()->comment('IP desde donde se generó el evento de seguridad');
            
            // Llave foránea limpia sin comentarios
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};