<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();

            // Relacionamento polimórfico (gera colunas anexavel_type e anexavel_id, com índice)
            $table->morphs('anexavel');

            // Metadados do anexo/contrato
            $table->string('tipo', 20)->default('contrato')->index();
            $table->string('arquivo');
            $table->string('descricao')->nullable();

            // Datas e status de assinatura
            $table->date('data_assinatura')->nullable();
            $table->date('data_vencimento')->nullable()->index();
            $table->boolean('assinado')->default(false);

            // >>> Novos campos para controle do percentual vigente <<<
            $table->decimal('percentual_vendas', 5, 2)->nullable();
            $table->boolean('ativo')->default(false);

            $table->timestamps();

            // Índice útil para recuperar rapidamente o "anexo ativo" do dono
            $table->index(['anexavel_type', 'anexavel_id', 'ativo'], 'anexos_anexavel_ativo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
