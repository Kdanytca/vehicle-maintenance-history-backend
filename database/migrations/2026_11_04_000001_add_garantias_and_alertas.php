<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repuestos', function (Blueprint $table) {
            $table->date('garantia_inicio')->nullable();
            $table->date('garantia_fin')->nullable()->index();
        });
        Schema::create('alertas_garantia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_repuesto');
            $table->foreign('id_repuesto')->references('id_repuesto')->on('repuestos')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha_vencimiento');
            $table->timestamp('atendida_at')->nullable()->index();
            $table->unsignedBigInteger('id_usuario_atencion')->nullable();
            $table->foreign('id_usuario_atencion')->references('id_usuario')->on('usuarios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['id_repuesto', 'tipo', 'fecha_vencimiento'], 'alerta_garantia_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_garantia');
        Schema::table('repuestos', function (Blueprint $table) {
            $table->dropIndex(['garantia_fin']);
            $table->dropColumn(['garantia_inicio', 'garantia_fin']);
        });
    }
};
