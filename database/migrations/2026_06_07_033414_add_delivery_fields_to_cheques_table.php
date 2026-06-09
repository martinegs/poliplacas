<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->foreignId('receptor_id')->nullable()->constrained('entidades')->onDelete('set null');
            $table->date('fecha_salida')->nullable();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->onDelete('set null');
            $table->foreignId('caja_egreso_id')->nullable()->constrained('cajas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropForeign(['receptor_id']);
            $table->dropForeign(['caja_id']);
            $table->dropForeign(['caja_egreso_id']);
            $table->dropColumn(['receptor_id', 'fecha_salida', 'caja_id', 'caja_egreso_id']);
        });
    }
};
