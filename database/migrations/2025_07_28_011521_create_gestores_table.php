<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gestores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // UF de atuação (separada do endereço cadastral)
            $table->string('estado_uf', 2)->nullable()->index();

            $table->string('razao_social');
            $table->string('cnpj', 18);
            $table->string('representante_legal');
            $table->string('cpf', 14);
            $table->string('rg', 30)->nullable();
            $table->string('telefone', 20)->nullable();

            // E-mail não obrigatório
            $table->string('email')->nullable();

            // Endereço (mesmo padrão de clientes)
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();

            // Regras contratuais
            $table->decimal('percentual_vendas', 5, 2)->default(0);
            $table->date('vencimento_contrato')->nullable();
            $table->boolean('contrato_assinado')->default(false);

            // (REMOVIDO) $table->string('contrato')->nullable();

            $table->timestamps();

            // Índices
            $table->index('cnpj');
            $table->index('cpf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestores');
    }
};
