<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colecao_id')->nullable()->constrained('colecoes')->onDelete('set null');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->string('imagem')->nullable();
            $table->integer('quantidade_estoque')->default(0);
            $table->string('titulo')->nullable();
            $table->string('isbn')->nullable();
            $table->string('autores')->nullable();
            $table->string('edicao')->nullable();
            $table->year('ano')->nullable();
            $table->integer('numero_paginas')->nullable();
            $table->decimal('peso', 8, 3)->nullable(); // Ex: 0.450 (kg)
            $table->enum('ano_escolar', ['Ens Inf', 'Fund 1', 'Fund 2', 'EM'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
