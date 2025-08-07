<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedido_produto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produto_id')->constrained()->onDelete('cascade');
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');

            $table->integer('quantidade');
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('desconto_aplicado', 5, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('peso_total_produto', 8, 2);
            $table->integer('caixas');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_produto');
    }
};
