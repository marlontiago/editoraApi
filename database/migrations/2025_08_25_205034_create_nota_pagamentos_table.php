<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nota_pagamentos', function (Blueprint $table) {
            $table->id();

            // Nota relacionada
            $table->foreignId('nota_fiscal_id')
                ->constrained('notas_fiscais')
                ->cascadeOnDelete();

            // Dados do pagamento
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor_pago', 15, 2);     // valor recebido (bruto p/ base de retenções)
            $table->decimal('valor_liquido', 15, 2);  // valor_pago - somatório das retenções em VALOR

            // ------------------------------
            // Retenções (snapshot duplo: % e VALOR)
            // Use sempre *_valor para somatórios em relatórios
            // ------------------------------
            $table->decimal('ret_irrf_perc',   5, 2)->nullable();
            $table->decimal('ret_irrf_valor', 15, 2)->nullable();

            $table->decimal('ret_iss_perc',    5, 2)->nullable();
            $table->decimal('ret_iss_valor',  15, 2)->nullable();

            $table->decimal('ret_inss_perc',   5, 2)->nullable();
            $table->decimal('ret_inss_valor', 15, 2)->nullable();

            $table->decimal('ret_pis_perc',    5, 2)->nullable();
            $table->decimal('ret_pis_valor',  15, 2)->nullable();

            $table->decimal('ret_cofins_perc', 5, 2)->nullable();
            $table->decimal('ret_cofins_valor',15, 2)->nullable();

            $table->decimal('ret_csll_perc',   5, 2)->nullable();
            $table->decimal('ret_csll_valor', 15, 2)->nullable();

            $table->decimal('ret_outros_perc', 5, 2)->nullable();
            $table->decimal('ret_outros_valor',15, 2)->nullable();

            // ------------------------------
            // Comissões - ADVOGADO (snapshot)
            // ------------------------------
            $table->boolean('adesao_ata')->default(false);
            $table->foreignId('advogado_id')->nullable()
                ->constrained('advogados')->nullOnDelete();

            $table->decimal('perc_comissao_advogado', 8, 4)->nullable(); // %
            $table->decimal('comissao_advogado',     12, 2)->default(0); // valor

            // ------------------------------
            // Comissões - DIRETOR (snapshot)
            // ------------------------------
            $table->foreignId('diretor_id')->nullable()
                ->constrained('diretor_comercials')->nullOnDelete();

            $table->decimal('perc_comissao_diretor',  8, 4)->nullable(); // %
            $table->decimal('comissao_diretor',      12, 2)->default(0); // valor

            // ------------------------------
            // Comissões - GESTOR (snapshot)
            // ------------------------------
            $table->decimal('perc_comissao_gestor',   8, 4)->nullable(); // %
            $table->decimal('comissao_gestor',       12, 2)->default(0); // valor

            // ------------------------------
            // Comissões - DISTRIBUIDOR (snapshot)
            // ------------------------------
            $table->decimal('perc_comissao_distribuidor', 8, 4)->nullable(); // %
            $table->decimal('comissao_distribuidor',     12, 2)->default(0); // valor

            // Momento do snapshot (opcional, útil para auditoria)
            $table->timestamp('comissao_snapshot_at')->nullable();

            // Observações livres
            $table->text('observacoes')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index(['nota_fiscal_id', 'data_pagamento']);
            $table->index(['advogado_id']);
            $table->index(['diretor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_pagamentos');
    }
};
