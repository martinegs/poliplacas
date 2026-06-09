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
            $table->foreignId('banco_id')->nullable()->constrained('bancos')->nullOnDelete();
        });

        // Migrate existing data
        $cheques = DB::table('cheques')->get();
        foreach ($cheques as $cheque) {
            $bancoNombre = isset($cheque->banco) ? trim($cheque->banco) : '';
            if (!empty($bancoNombre)) {
                $bancoId = DB::table('bancos')->where('nombre', $bancoNombre)->value('id');
                if (!$bancoId) {
                    $bancoId = DB::table('bancos')->insertGetId([
                        'nombre' => $bancoNombre,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('cheques')->where('id', $cheque->id)->update(['banco_id' => $bancoId]);
            }
        }

        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn('banco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->string('banco')->nullable();
        });

        // Restore string bank names from relationship
        $cheques = DB::table('cheques')->whereNotNull('banco_id')->get();
        foreach ($cheques as $cheque) {
            $bancoNombre = DB::table('bancos')->where('id', $cheque->banco_id)->value('nombre');
            if ($bancoNombre) {
                DB::table('cheques')->where('id', $cheque->id)->update(['banco' => $bancoNombre]);
            }
        }

        Schema::table('cheques', function (Blueprint $table) {
            $table->dropForeign(['banco_id']);
            $table->dropColumn('banco_id');
        });
    }
};
