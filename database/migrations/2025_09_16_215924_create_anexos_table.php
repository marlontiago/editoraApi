<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->morphs('anexavel'); // anexavel_type, anexavel_id
            // tipos: contrato, aditivo, outro, contrato_cidade
            $table->string('tipo', 20)->default('contrato')->index();
            $table->foreignId('cidade_id')->nullable()->constrained('cities'); // <- NOVO
            $table->string('arquivo');
            $table->string('descricao')->nullable();
            $table->date('data_assinatura')->nullable();
            $table->date('data_vencimento')->nullable()->index();
            $table->boolean('assinado')->default(false);
            $table->decimal('percentual_vendas', 5, 2)->nullable();
            $table->boolean('ativo')->default(false);
            $table->timestamps();

            $table->index(['anexavel_type', 'anexavel_id', 'ativo'], 'anexos_anexavel_ativo_idx');
            $table->index(['tipo', 'cidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
