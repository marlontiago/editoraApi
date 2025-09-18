<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;

class Distribuidor extends Model
{
    use HasFactory;

    protected $table = 'distribuidores';

    protected $fillable = [
        'user_id',
        'gestor_id',

        'razao_social',
        'cnpj',
        'representante_legal',
        'cpf',
        'rg',
        'telefone',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'percentual_vendas',
        'vencimento_contrato',
        'contrato_assinado',
    ];

    protected $casts = [
        'vencimento_contrato' => 'date',
        'contrato_assinado'   => 'boolean',
        'percentual_vendas'   => 'decimal:2',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function cities()
    {
        // pivot: city_distribuidor (city_id, distribuidor_id)
        return $this->belongsToMany(City::class, 'city_distribuidor');
    }

    public function anexos()
    {
        // anexo polimórfico (tabela "anexos")
        return $this->morphMany(Anexo::class, 'anexavel');
    }

    public function contatos()
    {
        return $this->morphMany(Contato::class, 'contatavel')
            ->orderByRaw("CASE WHEN preferencial THEN 0 ELSE 1 END")
            ->orderBy('tipo')
            ->orderBy('nome');
    }

    /* =======================
     |  Helpers de formatação
     |=======================*/
    public function getCnpjFormatadoAttribute(): string
    {
        return Formatters::formatCnpj($this->cnpj);
    }

    public function getTelefoneFormatadoAttribute(): string
    {
        return Formatters::formatTelefone($this->telefone);
    }

    // por consistência com o Gestor (exibição simples do e-mail do user)
    public function getEmailExibicaoAttribute(): string
    {
        return $this->user?->email ?: 'Não informado';
    }

    // compat: alguns lugares podem usar $distribuidor->estado_uf
    public function getEstadoUfAttribute(): ?string
    {
        return $this->uf;
    }
}
