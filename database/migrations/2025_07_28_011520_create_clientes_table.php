<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('razao_social');
            $table->string('email')->unique();
            $table->string('cnpj', 18)->nullable();
            $table->string('cpf', 14)->nullable();  
            $table->string('inscr_estadual', 30)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable(); 

            $table->timestamps();

            $table->index('cnpj');
            $table->index('cpf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
