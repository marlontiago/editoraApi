<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gestores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable();
            $table->string('estado_uf', 2)->nullable()->index();

            $table->string('razao_social');
            $table->string('cnpj', 18)->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('cpf', 14)->nullable();
            $table->string('rg', 30)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->json('telefones')->nullable();
            $table->json('emails')->nullable();

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

            // Contratuais
            // Base do cadastro (nunca deve ser sobrescrito por contrato)
            $table->decimal('percentual_vendas_base', 5, 2)->default(0);

            // Percentual vigente (pode ser base ou do contrato ativo)
            $table->decimal('percentual_vendas', 5, 2)->default(0);

            $table->date('vencimento_contrato')->nullable();
            $table->boolean('contrato_assinado')->default(false);

            $table->timestamps();
            $table->softDeletes(); // deleted_at

            $table->index('cnpj');
            $table->index('cpf');
        });

        Schema::table('gestores', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestores');
    }
};
