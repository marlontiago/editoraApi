<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{

    use HasFactory;
    protected $table = 'clientes';
    protected $fillable = [
           'user_id',
            'razao_social','email',
            'cnpj','cpf','inscr_estadual',
            'telefone',
            'endereco','numero','complemento','bairro','cidade','uf','cep',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Adicione outros relacionamentos ou métodos conforme necessário
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function getCpfFormatadoAttribute(): string
    {
        return Formatters::formatCpf($this->cpf);
    }

    public function getCnpjFormatadoAttribute(): string
    {
        return Formatters::formatCnpj($this->cnpj);
    }

    public function getTelefoneFormatadoAttribute(): string
    {
        return Formatters::formatTelefone($this->telefone);
    }
    public function getRgFormatadoAttribute(): string
    {
        return Formatters::formatRg($this->rg);
    }


    

}
