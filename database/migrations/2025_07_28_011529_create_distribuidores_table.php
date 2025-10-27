<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distribuidores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gestor_id')->nullable()->constrained('gestores')->nullOnDelete();

            // Dados principais
            $table->string('razao_social')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();

            $table->json('emails')->nullable();     
            $table->json('telefones')->nullable();  

            // Endereço principal
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();

            // Endereço secundário
            $table->string('endereco2', 255)->nullable();
            $table->string('numero2', 20)->nullable();
            $table->string('complemento2', 100)->nullable();
            $table->string('bairro2', 100)->nullable();
            $table->string('cidade2', 100)->nullable();
            $table->string('uf2', 2)->nullable();
            $table->string('cep2', 9)->nullable();

            // Contrato
            $table->decimal('percentual_vendas', 5, 2)->default(0)->nullable();
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
