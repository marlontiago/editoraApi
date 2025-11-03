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

            // CFOP (4 dígitos)
            $table->string('cfop', 4)->nullable()->index();

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

        // PostgreSQL: constraints e índices específicos
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            // Permitir apenas 1 nota "emitida" por pedido.
            DB::statement("
                CREATE UNIQUE INDEX notas_uma_emitida_por_pedido
                ON notas_fiscais (pedido_id)
                WHERE status = 'emitida'
            ");

            // Índice para acelerar cálculo de próximo número (cast para int)
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

            // CHECK: cfop deve ter exatamente 4 dígitos (ou ser NULL)
            DB::statement("
                ALTER TABLE notas_fiscais
                ADD CONSTRAINT notas_fiscais_cfop_chk
                CHECK (cfop IS NULL OR cfop ~ '^[0-9]{4}$')
            ");

            // Índice explícito para cfop (além do ->index(); fica idempotente)
            DB::statement("CREATE INDEX IF NOT EXISTS notas_fiscais_cfop_idx ON notas_fiscais (cfop)");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS notas_uma_emitida_por_pedido");
            DB::statement("DROP INDEX IF EXISTS notas_numero_int_idx");
            DB::statement("DROP INDEX IF EXISTS notas_numero_unico_quando_preenchido");
            DB::statement("ALTER TABLE notas_fiscais DROP CONSTRAINT IF EXISTS notas_fiscais_cfop_chk");
            DB::statement("DROP INDEX IF EXISTS notas_fiscais_cfop_idx");
        }

        Schema::dropIfExists('notas_fiscais');
    }
};
