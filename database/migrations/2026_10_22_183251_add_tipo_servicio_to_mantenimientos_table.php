<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->string('tipo_servicio', 100)->nullable()->after('fecha_servicio');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn('tipo_servicio');
        });
    }
};