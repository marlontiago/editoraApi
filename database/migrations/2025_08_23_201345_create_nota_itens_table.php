<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nota_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nota_fiscal_id')->constrained('notas_fiscais')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos');

            // Quantidades e valores capturados no momento da emissão
            $table->integer('quantidade');
            $table->decimal('preco_unitario', 12, 2);
            $table->decimal('desconto_aplicado', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('peso_total_produto', 12, 3)->default(0);
            $table->integer('caixas')->default(0);

            // Snapshot de descrição (evita mudar se produto for editado depois)
            $table->string('descricao_produto')->nullable();
            $table->string('isbn')->nullable();    // se aplicável no seu catálogo
            $table->string('titulo')->nullable();  // idem

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_itens');
    }
};
