<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\Formatters;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'razao_social', 'email',

        // documentos
        'cnpj', 'cpf', 'inscr_estadual',

        // legado (um único telefone)
        'telefone',

        // novos (listas)
        'telefones', 'emails',

        // endereço principal
        'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'cep',

        // endereço secundário (novos)
        'endereco2', 'numero2', 'complemento2', 'bairro2', 'cidade2', 'uf2', 'cep2',
    ];

    protected $casts = [
        'telefones' => 'array',
        'emails'    => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors de Formatação (compat com seus helpers atuais)
    |--------------------------------------------------------------------------
    */
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
        // compat: formata o campo legado único
        return Formatters::formatTelefone($this->telefone);
    }

    /**
     * Telefones formatados (lista nova). Se vazia, cai no legado único.
     * Ex.: $cliente->telefones_formatados => ['(11) 99999-9999', '(11) 3333-3333']
     */
    public function getTelefonesFormatadosAttribute(): array
    {
        $lista = is_array($this->telefones) ? $this->telefones : [];
        if (empty($lista) && $this->telefone) {
            $lista = [$this->telefone]; // fallback legado
        }

        return collect($lista)
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->map(fn ($t) => Formatters::formatTelefone($t))
            ->values()
            ->all();
    }

    /**
     * E-mails normalizados da lista (sem o principal obrigatório).
     * Ex.: $cliente->emails_limpos => ['financeiro@x.com', 'outro@y.com']
     */
    public function getEmailsLimposAttribute(): array
    {
        return collect(is_array($this->emails) ? $this->emails : [])
            ->map(fn ($e) => trim((string) $e))
            ->filter()
            ->values()
            ->all();
    }
}
