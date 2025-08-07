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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestor_id')->nullable()->constrained('gestores')->onDelete('set null');
            $table->foreignId('distribuidor_id')->nullable()->constrained('distribuidores')->onDelete('set null');
            $table->date('data');
            $table->decimal('desconto', 5, 2)->default(0); // ex: 20.00
            $table->decimal('peso_total', 8, 2)->default(0); // kg
            $table->integer('total_caixas')->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->enum('status', ['em_andamento', 'enviado', 'aprovado', 'rejeitado'])->default('em_andamento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
