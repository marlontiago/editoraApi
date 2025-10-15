<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distribuidores', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // vínculo opcional com gestor
            $table->foreignId('gestor_id')
                ->nullable()
                ->constrained('gestores')
                ->nullOnDelete();

            // Dados principais
            $table->string('razao_social');
            $table->string('cnpj');
            $table->string('representante_legal');
            $table->string('cpf');
            $table->string('rg')->nullable();

            // Contatos (agora como listas JSON)
            // O primeiro e-mail da lista será usado para a conta do usuário
            $table->json('emails')->nullable();     // ex: ["a@x.com","b@y.com"]
            $table->json('telefones')->nullable();  // ex: ["41999999999","4133333333"]

            // Endereço principal
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();

            // Endereço secundário (mesma estrutura)
            $table->string('endereco2', 255)->nullable();
            $table->string('numero2', 20)->nullable();
            $table->string('complemento2', 100)->nullable();
            $table->string('bairro2', 100)->nullable();
            $table->string('cidade2', 100)->nullable();
            $table->string('uf2', 2)->nullable();
            $table->string('cep2', 9)->nullable();

            // Comercial / Contrato
            $table->decimal('percentual_vendas', 5, 2)->default(0);
            $table->date('vencimento_contrato')->nullable();
            $table->boolean('contrato_assinado')->default(false);

            $table->timestamps();

            // Índices úteis
            $table->index('cnpj');
            $table->index('cpf');
            $table->index(['gestor_id', 'uf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribuidores');
    }
};
