<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();

            // Polimórfico: dono do contato (Gestor, Distribuidor, Cliente, etc.)
            $table->morphs('contatavel'); // contatavel_type, contatavel_id (índices incluídos)

            // Dados do contato
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('cargo', 100)->nullable();

            // Classificação — usamos string para evitar dor de cabeça de enum cross-DB
            $table->string('tipo', 20)->default('outro'); // principal, secundario, financeiro, comercial, outro
            $table->boolean('preferencial')->default(false);

            $table->text('observacoes')->nullable();

            $table->timestamps();

            // (Opcional, apenas Postgres) Único preferencial por dono:
            // $table->unique(['contatavel_type','contatavel_id'], 'contatos_preferencial_unique')
            //     ->where('preferencial', true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};
