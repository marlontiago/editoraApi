<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela pivô das UFs do Gestor
        Schema::create('gestor_ufs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestor_id')->constrained('gestores')->cascadeOnDelete();
            $table->string('uf', 2);
            $table->timestamps();

            // Cada UF só pode pertencer a UM gestor no sistema inteiro
            $table->unique('uf', 'gestor_ufs_uf_unique');

            // Índices úteis
            $table->index('gestor_id');
        });

        // Se em algum dump antigo a coluna existir, remove (fresh não terá, mas não custa)
        if (Schema::hasColumn('gestores', 'estado_uf')) {
            Schema::table('gestores', function (Blueprint $table) {
                $table->dropColumn('estado_uf');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gestor_ufs');

        // (Opcional) recriar a coluna antiga se quiser compat
        if (!Schema::hasColumn('gestores', 'estado_uf')) {
            Schema::table('gestores', function (Blueprint $table) {
                $table->string('estado_uf', 2)->nullable()->after('rg');
            });
        }
    }
};
