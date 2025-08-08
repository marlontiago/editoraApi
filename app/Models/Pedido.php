<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'user_id',
        'gestor_id',
        'distribuidor_id',
        'cidade_id',
        'data',
        'desconto',
        'peso_total',
        'total_caixas',
        'valor_bruto',
        'valor_total',
        'status',
    ];


    public function produtos()
    {
        return $this->belongsToMany(Produto::class, 'pedido_produto')
            ->withPivot([
                'quantidade',
                'preco_unitario',
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

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function registrarLog(string $acao, ?string $detalhes = null, array $changes = [])
    {
        $this->logs()->create([
            'user_id'  => auth()->id(),
            'acao'     => $acao,
            'detalhes' => $detalhes,
            'changes'  => $changes ?: null,
        ]);
    }

}
