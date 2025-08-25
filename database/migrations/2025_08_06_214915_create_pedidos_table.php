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
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            
            $table->date('data');
            $table->decimal('peso_total', 8, 2)->default(0); // kg
            $table->integer('total_caixas')->default(0);
            $table->decimal('valor_bruto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->enum('status', ['em_andamento', 'finalizado', 'cancelado'])->default('em_andamento');
            $table->text('observacoes')->nullable()->after('status');
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
