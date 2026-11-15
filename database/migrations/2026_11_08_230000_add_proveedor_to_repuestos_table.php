<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repuestos', function (Blueprint $table) {
            if (!Schema::hasColumn('repuestos', 'proveedor')) {
                $table->string('proveedor', 100)->nullable()->after('codigo_pieza');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repuestos', function (Blueprint $table) {
            if (Schema::hasColumn('repuestos', 'proveedor')) {
                $table->dropColumn('proveedor');
            }
        });
    }
};

