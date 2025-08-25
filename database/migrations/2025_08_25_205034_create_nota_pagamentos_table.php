<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nota_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_fiscal_id')->constrained('notas_fiscais')->cascadeOnDelete();

            // pagamento
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor_pago', 15, 2); // valor efetivamente recebido (bruto p/ base de retenções)

            // retenções (deixe nullable para campos não usados)
            $table->decimal('ret_irrf',   15, 2)->nullable();
            $table->decimal('ret_iss',    15, 2)->nullable();
            $table->decimal('ret_inss',   15, 2)->nullable();
            $table->decimal('ret_pis',    15, 2)->nullable();
            $table->decimal('ret_cofins', 15, 2)->nullable();
            $table->decimal('ret_csll',   15, 2)->nullable();
            $table->decimal('ret_outros', 15, 2)->nullable();

            // adesão à ata
            $table->boolean('adesao_ata')->default(false);
            $table->foreignId('advogado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('perc_comissao_advogado', 5, 2)->nullable(); // %
            $table->foreignId('diretor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('perc_comissao_diretor', 5, 2)->nullable();   // %

            // valores calculados (snapshot na criação)
            $table->decimal('valor_liquido', 15, 2);            // valor_pago - somatório das retenções
            $table->decimal('comissao_advogado', 15, 2)->default(0);
            $table->decimal('comissao_diretor', 15, 2)->default(0);

            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('nota_pagamentos');
    }
};
