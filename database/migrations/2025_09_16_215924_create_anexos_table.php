<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();

            $table->morphs('anexavel');
            
            $table->string('tipo', 20)->default('contrato')->index(); 
            $table->string('arquivo'); 
            $table->string('descricao')->nullable();

            $table->date('data_assinatura')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->boolean('assinado')->default(false);

            $table->timestamps();
            $table->index('data_vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
