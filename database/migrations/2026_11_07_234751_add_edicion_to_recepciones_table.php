<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * G2DS-16: datos de la última corrección hecha a una recepción.
 * El detalle completo de cada cambio (antes, después y motivo) queda en la auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recepciones', function (Blueprint $table) {
            $table->timestamp('editado_at')->nullable()->comment('Fecha de la última corrección');
            $table->unsignedBigInteger('id_usuario_edicion')->nullable()->comment('Administrador que hizo la última corrección');
            $table->foreign('id_usuario_edicion')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->text('motivo_edicion')->nullable()->comment('Motivo de la última corrección');
        });
    }

    public function down(): void
    {
        Schema::table('recepciones', function (Blueprint $table) {
            $table->dropForeign(['id_usuario_edicion']);
            $table->dropColumn(['editado_at', 'id_usuario_edicion', 'motivo_edicion']);
        });
    }
};