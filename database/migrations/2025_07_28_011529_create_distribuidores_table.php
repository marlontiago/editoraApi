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
            $table->foreignId('gestor_id')->constrained('gestores')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('cnpj');
            $table->string('representante_legal');
            $table->string('cpf');
            $table->string('rg');
            $table->string('telefone')->nullable();
            $table->string('endereco_completo')->nullable();
            $table->decimal('percentual_vendas', 5, 2)->default(0);
            $table->date('vencimento_contrato')->nullable();
            $table->boolean('contrato_assinado')->default(false);
            $table->string('contrato')->nullable(); // path do arquivo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribuidores');
    }
};
