<?php

// 2026_05_27_225717_create_notificaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->string('destinatario', 100)->comment('Correo electrónico de destino');
            $table->string('asunto', 150)->comment('Título o asunto del correo');
            $table->text('mensaje')->comment('Contenido del cuerpo del mensaje');
            $table->string('tipo_envio', 20)->comment('Diferencia si es Automático o Manual');
            $table->timestamp('fecha_envio')->nullable()->comment('Registro de cuándo se envió el correo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
