<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id('id_propietario');
            $table->string('nombre', 100)->comment('Nombre completo del cliente propietario');
            $table->string('documento_identidad', 20)->comment('Documento único de identificación del cliente');
            $table->string('telefono', 20)->nullable()->comment('Teléfono de contacto del propietario');
            $table->string('correo', 100)->nullable()->comment('Correo electrónico para enviar notificaciones');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};