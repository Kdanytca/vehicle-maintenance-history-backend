<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repuestos', function (Blueprint $table) {
            $table->id('id_repuesto');
            $table->string('nombre_pieza', 100)->comment('Nombre comercial del repuesto o pieza');
            $table->string('codigo_pieza', 50)->nullable()->comment('Código de barra o SKU de inventario');
            $table->decimal('costo_unitario', 10, 2)->comment('Precio de costo de la pieza');

            // Columnas limpias que aceptan nulos
            $table->unsignedBigInteger('id_mantenimiento')->nullable();
            $table->unsignedBigInteger('id_factura')->nullable();

            $table->timestamps();
        });

        // 2. Acoplamos las llaves foráneas de forma segura
        Schema::table('repuestos', function (Blueprint $table) {
            // Enlace con mantenimientos (si existe la tabla)
            if (Schema::hasTable('mantenimientos')) {
                $table->foreign('id_mantenimiento')
                    ->references('id_mantenimiento')
                    ->on('mantenimientos')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('facturas')) {
                $table->foreign('id_factura')
                    ->references('id_factura')
                    ->on('facturas')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repuestos');
    }
};
