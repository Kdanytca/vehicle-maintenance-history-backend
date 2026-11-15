<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('usuario_nombre', 100)->nullable()->index();
            $table->string('rol_nombre', 100)->nullable();
            $table->string('tipo_acceso', 20)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['usuario_nombre']);
            $table->dropIndex(['tipo_acceso']);
            $table->dropColumn(['usuario_nombre', 'rol_nombre', 'tipo_acceso']);
        });
    }
};
