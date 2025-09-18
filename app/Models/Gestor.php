<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;

class Gestor extends Model
{
    use HasFactory;

    protected $table = 'gestores';

    protected $fillable = [
        'user_id',
        'estado_uf',            // UF de atuação
        'razao_social',
        'cnpj',
        'representante_legal',
        'cpf',
        'rg',
        'telefone',
        'email',

        // Endereço (como no cliente)
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',                   // UF do endereço
        'cep',

        // Contratuais
        'percentual_vendas',
        'vencimento_contrato',
        'contrato_assinado',
    ];

    protected $casts = [
        'vencimento_contrato' => 'date',
        'contrato_assinado'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function distribuidores()
    {
        return $this->hasMany(Distribuidor::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'city_gestor');
    }

    public function anexos()
    {
        return $this->morphMany(\App\Models\Anexo::class, 'anexavel');
    }

    public function contatos()
    {
        return $this->morphMany(\App\Models\Contato::class, 'contatavel')
            ->orderByRaw("CASE WHEN preferencial THEN 0 ELSE 1 END")
            ->orderBy('tipo')
            ->orderBy('nome');
    }

    // ===== Helpers de formatação =====
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

    // NÃO sobrescreva "uf" para não conflitar com a coluna do endereço.
    // Removido o getUfAttribute().

    public function getEmailExibicaoAttribute(): string
    {
        // 1) Se o e-mail do próprio gestor estiver preenchido, use-o
        $emailGestor = trim((string) $this->email);
        if ($emailGestor !== '') {
            return $emailGestor;
        }

        // 2) Senão, olhe o e-mail do usuário relacionado
        $emailUser = trim((string) ($this->user->email ?? ''));

        // Considere placeholders como "não informado"
        $isPlaceholder =
            $emailUser === '' ||
            preg_match('/^gestor\+.+@placeholder\.local$/i', $emailUser) ||
            str_ends_with($emailUser, '@placeholder.local');

        if ($isPlaceholder) {
            return 'Não informado';
        }

        // 3) Se for um e-mail real, mostre
        return $emailUser;
    }
}
