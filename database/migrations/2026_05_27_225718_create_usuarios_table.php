<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('username', 50)->unique()->comment('Nombre de usuario único para el acceso al sistema');
            $table->string('password_hash', 255)->comment('Contraseña encriptada para seguridad');
            $table->string('remember_token', 100)->nullable()->comment('Token para recordar sesión');
            $table->boolean('estado_activo')->default(true)->comment('Indica si el usuario está habilitado o suspendido');

            // Llave foránea limpia sin comentarios
            $table->unsignedBigInteger('id_rol')->nullable();
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
