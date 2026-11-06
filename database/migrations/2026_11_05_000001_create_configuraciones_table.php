<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->unsignedInteger('valor');
            $table->timestamps();
        });

        DB::table('configuraciones')->insert([
            'clave' => 'dias_alerta_garantia',
            'valor' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
