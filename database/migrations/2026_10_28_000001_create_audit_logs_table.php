<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla de auditoría del sistema.
     */
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('accion', 50);
            $table->string('modulo', 100);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración eliminando la tabla si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
