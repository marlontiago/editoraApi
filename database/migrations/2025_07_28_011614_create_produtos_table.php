<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            // FK opcional para coleções (se apagar a coleção, zera o campo)
            $table->foreignId('colecao_id')
                ->nullable()
                ->constrained('colecoes')
                ->nullOnDelete();

            // Dados principais
            $table->integer('codigo')->unique();  
            $table->string('titulo')->nullable();          // Controller valida como required na criação
            $table->text('descricao')->nullable();
            $table->string('isbn')->nullable()->index();   // opcional, index para busca
            $table->string('autores')->nullable();
            $table->string('edicao')->nullable();
            $table->year('ano')->nullable();               // mapeado como smallint/int pelo Laravel
            $table->integer('numero_paginas')->nullable();

            // Atributos comerciais/logísticos
            $table->decimal('preco', 10, 2)->nullable();
            $table->decimal('peso', 8, 3)->nullable();     // ex.: 0.450 (kg)
            $table->integer('quantidade_estoque')->nullable();
            $table->integer('quantidade_por_caixa')->default(1);

            // Escolaridade (seu enum original)
            $table->enum('ano_escolar', ['Ens Inf', 'Fund 1', 'Fund 2', 'EM'])->nullable();

            // Mídia
            $table->string('imagem')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices úteis
            $table->index('titulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
