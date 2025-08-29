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

            // Pedido associado
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();

            // Numeração
            $table->string('numero')->nullable(); 
            $table->string('serie')->default('1');

            // Status financeiro: aguardando_pagamento | pago
            $table->string('status_financeiro')->nullable()->index();
            $table->timestamp('pago_em')->nullable();

            // Status da nota: emitida | faturada | cancelada
            $table->enum('status', ['emitida', 'faturada', 'cancelada'])->default('emitida');

            // Totais (espelhados do pedido no momento da emissão)
            $table->decimal('valor_bruto', 12, 2)->default(0);
            $table->decimal('desconto_total', 12, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->decimal('peso_total', 12, 3)->default(0);
            $table->integer('total_caixas')->default(0);

            // Snapshots (auditoria)
            $table->json('emitente_snapshot')->nullable();
            $table->json('destinatario_snapshot')->nullable();
            $table->json('pedido_snapshot')->nullable();

            // Integração futura NF-e
            $table->string('chave_acesso')->nullable();
            $table->string('protocolo')->nullable();
            $table->string('ambiente')->default('interno'); // interno|homologacao|producao

            // Controle de datas
            $table->timestamp('emitida_em')->nullable();
            $table->timestamp('faturada_em')->nullable();

            // Cancelamento/substituição
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

            // Índice para acelerar cálculo de próximo número
            DB::statement("
                CREATE INDEX IF NOT EXISTS notas_numero_int_idx
                ON notas_fiscais ((NULLIF(numero,'')::int))
            ");

            // Unicidade de número quando informado
            DB::statement("
                CREATE UNIQUE INDEX IF NOT EXISTS notas_numero_unico_quando_preenchido
                ON notas_fiscais (numero)
                WHERE numero IS NOT NULL AND numero <> ''
            ");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS notas_uma_emitida_por_pedido");
            DB::statement("DROP INDEX IF EXISTS notas_numero_int_idx");
            DB::statement("DROP INDEX IF EXISTS notas_numero_unico_quando_preenchido");
        }
        Schema::dropIfExists('notas_fiscais');
    }
};
