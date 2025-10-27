<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gestor_ufs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gestor_id');
            $table->string('uf', 2); // UF de atuação

            $table->timestamps();

            $table->unique(['gestor_id', 'uf'], 'gestor_ufs_gestor_id_uf_unique');
            $table->index('uf', 'gestor_ufs_uf_index');
        });

        Schema::table('gestor_ufs', function (Blueprint $table) {
            $table->foreign('gestor_id')
                  ->references('id')->on('gestores')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestor_ufs');
    }
};
