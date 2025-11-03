<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id',
        'gestor_id',
        'distribuidor_id',
        'data',
        'peso_total',
        'total_caixas',
        'valor_bruto',
        'valor_total',
        'status',
        'observacoes',
        'cfop',
    ];

    protected $casts = [
        'data' => 'date',
        'meta' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */
    public function produtos()
    {
        return $this->belongsToMany(Produto::class, 'pedido_produto')
            ->withPivot([
                'quantidade',
                'preco_unitario',
                'desconto_item',
                'desconto_aplicado',
                'subtotal',
                'peso_total_produto',
                'caixas',
            ])->withTimestamps();
    }

    public function cidades()
    {
        return $this->belongsToMany(City::class, 'cidade_pedido');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function distribuidor()
    {
        return $this->belongsTo(Distribuidor::class);
    }

    public function logs()
    {
        return $this->hasMany(PedidoLog::class)->latest();
    }

    // (Parece supérfluo / possivelmente incorreto; mantendo para compatibilidade)
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function notaFiscal()
    {
        return $this->hasOne(NotaFiscal::class, 'pedido_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers CFOP (opcionais)
    |--------------------------------------------------------------------------
    | Úteis para exibir comportamento esperado já no pedido.
    */
    public function isSimplesRemessa(): bool
    {
        return in_array($this->cfop, config('cfop.simples_remessa', []), true);
    }

    public function isBonificacao(): bool
    {
        return in_array($this->cfop, config('cfop.bonificacao', []), true);
    }

    /*
    |--------------------------------------------------------------------------
    | Log
    |--------------------------------------------------------------------------
    */
    public function registrarLog(string $acao, ?string $detalhes = null, array $meta = []): void
    {
        $this->logs()->create([
            'acao'     => $acao,
            'detalhes' => $detalhes,
            'meta'     => $meta ?: null,
            'user_id'  => auth()->id(),
        ]);
    }
}
