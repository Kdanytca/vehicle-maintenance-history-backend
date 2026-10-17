<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // La autenticación del proyecto usa usuarios.id_usuario.
            $table->foreignId('user_id')->constrained('usuarios', 'id_usuario')->restrictOnDelete();
            $table->string('accion');
            $table->string('modulo');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
