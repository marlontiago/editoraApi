<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribuidor_id')->constrained('distribuidores')->cascadeOnDelete();
            $table->foreignId('gestor_id')->constrained('gestores')->cascadeOnDelete(); // denormalizado p/ facilitar relatórios
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->integer('quantidade');
            $table->decimal('valor_total', 12, 2);

            // Snapshot da comissão do DISTRIBUIDOR no momento da venda
            $table->decimal('commission_percentage_snapshot', 5, 2)->nullable();
            $table->decimal('commission_value_snapshot', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendas');
    }
};
