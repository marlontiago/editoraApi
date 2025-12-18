<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('colecao_id')
                ->nullable()
                ->constrained('colecoes')
                ->nullOnDelete();

            $table->integer('codigo')->unique();
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->string('isbn')->nullable()->index();
            $table->string('autores')->nullable();
            $table->string('edicao')->nullable();
            $table->year('ano')->nullable();
            $table->integer('numero_paginas')->nullable();

            $table->decimal('preco', 10, 2)->nullable();
            $table->decimal('peso', 8, 3)->nullable();
            $table->integer('quantidade_estoque')->nullable();
            $table->integer('quantidade_por_caixa')->default(1);

            $table->string('ano_escolar', 255)->nullable();

            $table->string('ncm', 10)->nullable()->index();     
            $table->string('cest', 7)->nullable()->index();    
            $table->unsignedTinyInteger('origem')->nullable(); 

            $table->string('imagem')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('titulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
