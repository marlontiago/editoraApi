<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

            $table->enum('status', ['em_andamento', 'finalizado', 'cancelado', 'pre_aprovado'])->default('em_andamento');

            $table->string('cfop', 4)->nullable()->index();

            $table->text('observacoes')->nullable();
            $table->timestamps();

            // Índices úteis
            $table->index(['status', 'data']);
        });

        // CHECK específico para PostgreSQL: CFOP deve ter 4 dígitos (ou ser NULL)
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE pedidos
                ADD CONSTRAINT pedidos_cfop_chk
                CHECK (cfop IS NULL OR cfop ~ '^[0-9]{4}$')
            ");
            DB::statement("CREATE INDEX IF NOT EXISTS pedidos_cfop_idx ON pedidos (cfop)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pedidos DROP CONSTRAINT IF EXISTS pedidos_cfop_chk");
            DB::statement("DROP INDEX IF EXISTS pedidos_cfop_idx");
        }
        Schema::dropIfExists('pedidos');
    }
};
