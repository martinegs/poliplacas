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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->string('banco');
            $table->decimal('monto', 15, 2);
            $table->date('fecha_cobro');
            $table->foreignId('entidad_id')->nullable()->constrained('entidades')->onDelete('set null');
            $table->string('estado')->default('pendiente'); // pendiente, cobrado, rechazado, etc.
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
