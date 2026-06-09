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
            $table->foreignId('caja_cobro_id')->nullable()->constrained('cajas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropForeign(['caja_cobro_id']);
            $table->dropColumn(['caja_cobro_id']);
        });
    }
};
