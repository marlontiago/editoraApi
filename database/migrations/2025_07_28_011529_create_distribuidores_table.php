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
                ->constrained()            // referencia 'users.id'
                ->cascadeOnDelete();       // ao apagar user, apaga distribuidor

            // ATENÇÃO: vínculo opcional com gestor (pode ficar sem vínculo)
            $table->foreignId('gestor_id')
                ->nullable()
                ->constrained('gestores')  // referencia 'gestores.id'
                ->nullOnDelete();          // ao apagar gestor, seta NULL no distribuidor

            // Dados principais
            $table->string('razao_social');
            $table->string('cnpj');
            $table->string('representante_legal');
            $table->string('cpf');
            $table->string('rg')->nullable();
            $table->string('telefone', 20)->nullable();

            // Endereço
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();

            // Comercial / Contrato
            $table->decimal('percentual_vendas', 5, 2)->default(0);
            $table->date('vencimento_contrato')->nullable(); // calculado por início+validade no controller
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
