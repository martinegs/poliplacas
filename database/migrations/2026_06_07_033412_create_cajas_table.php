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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 15, 2);
            $table->string('tipo'); // efectivo, cheque, etc.
            $table->string('movimiento'); // ingreso, egreso
            $table->string('motivo');
            $table->foreignId('entidad_id')->nullable()->constrained('entidades')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
