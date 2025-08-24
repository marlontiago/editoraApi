<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();

            
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();

            
            $table->string('numero')->nullable(); 
            $table->string('serie')->nullable();

            // Status da nota
            // emitida = gerada a partir do pedido (sem baixar estoque)
            // faturada = estoque baixado
            // cancelada = cancelada/substituída
            $table->enum('status', ['emitida', 'faturada', 'cancelada'])->default('emitida');

            // Totais (espelhados do pedido no momento da emissão)
            $table->decimal('valor_bruto', 12, 2)->default(0);
            $table->decimal('desconto_total', 12, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->decimal('peso_total', 12, 3)->default(0);
            $table->integer('total_caixas')->default(0);

            // Snapshots (auditoria)
            $table->json('emitente_snapshot')->nullable();     // dados da empresa emitente
            $table->json('destinatario_snapshot')->nullable(); // dados do cliente
            $table->json('pedido_snapshot')->nullable();       // cabeçalho do pedido

            // Integração futura NF-e
            $table->string('chave_acesso')->nullable();
            $table->string('protocolo')->nullable();
            $table->string('ambiente')->default('interno'); // interno|homologacao|producao

            // Controle de datas
            $table->timestamp('emitida_em')->nullable();
            $table->timestamp('faturada_em')->nullable();

            // Campos de cancelamento/substituição
            $table->timestamp('cancelada_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index('pedido_id');
            $table->index(['status', 'pedido_id']);
        });

        // PostgreSQL: índice único PARCIAL para permitir apenas 1 nota "emitida" por pedido.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("
                CREATE UNIQUE INDEX notas_uma_emitida_por_pedido
                ON notas_fiscais (pedido_id)
                WHERE status = 'emitida'
            ");
        } else {
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS notas_uma_emitida_por_pedido");
        }
        Schema::dropIfExists('notas_fiscais');
    }
};
