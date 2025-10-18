<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;
use Carbon\Carbon;

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

        // compat legada (mantidos)
        'telefone',
        'email',

        // NOVOS: listas
        'telefones',
        'emails',

        // Endereço principal
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',                   // UF do endereço
        'cep',

        // NOVO: endereço secundário
        'endereco2',
        'numero2',
        'complemento2',
        'bairro2',
        'cidade2',
        'uf2',
        'cep2',

        // Contratuais
        'percentual_vendas',
        'vencimento_contrato',
        'contrato_assinado',
    ];

    protected $casts = [
        // listas
        'telefones'           => 'array',
        'emails'              => 'array',

        // contratuais
        'percentual_vendas'   => 'decimal:2',
        'vencimento_contrato' => 'date',
        'contrato_assinado'   => 'boolean',
    ];

    /* =======================
     |  Relacionamentos
     |=======================*/
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function distribuidores()
    {
        return $this->hasMany(Distribuidor::class);
    }

    public function cities()
    {
        // pivot: city_gestor (city_id, gestor_id)
        return $this->belongsToMany(City::class, 'city_gestor');
    }

    public function anexos()
    {
        // anexo polimórfico
        return $this->morphMany(\App\Models\Anexo::class, 'anexavel');
    }

    public function contatos()
    {
        return $this->morphMany(\App\Models\Contato::class, 'contatavel')
            ->orderByRaw("CASE WHEN preferencial THEN 0 ELSE 1 END")
            ->orderBy('tipo')
            ->orderBy('nome');
    }

    public function ufs()
    {
        return $this->hasMany(\App\Models\GestorUf::class);
    }

    /** Retorna array de UFs (['SP','RJ',...]) */
    public function getUfsListAttribute(): array
    {
        return $this->ufs->pluck('uf')->map(fn($u)=>strtoupper($u))->unique()->values()->all();
    }

    /* =======================
     |  Helpers de formatação
     |=======================*/
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
        // mantém compat com campo legado único
        return Formatters::formatTelefone($this->telefone);
    }

    public function getRgFormatadoAttribute(): string
    {
        return Formatters::formatRg($this->rg);
    }

    // NÃO sobrescreva "uf" (para não conflitar com a coluna do endereço)
    // Removido getUfAttribute().

    // Exibição simples do e-mail
    public function getEmailExibicaoAttribute(): string
    {
        // (1) Se o e-mail do próprio gestor estiver preenchido, use-o
        $emailGestor = trim((string) $this->email);
        if ($emailGestor !== '') {
            return $emailGestor;
        }

        // (2) Senão, olhe o e-mail do usuário relacionado
        $emailUser = trim((string) ($this->user->email ?? ''));

        // Considere placeholders como "não informado"
        $isPlaceholder =
            $emailUser === '' ||
            preg_match('/^gestor\+.+@placeholder\.local$/i', $emailUser) ||
            str_ends_with($emailUser, '@placeholder.local');

        if ($isPlaceholder) {
            return 'Não informado';
        }

        // (3) Se for um e-mail real, mostre
        return $emailUser;
    }

    /* =======================
     |  Scopes e atributos
     |=======================*/
    public function scopeVencendoEmAte($q, int $dias = 30)
    {
        return $q->whereNotNull('vencimento_contrato')
                 ->whereDate('vencimento_contrato', '>=', now()->toDateString())
                 ->whereDate('vencimento_contrato', '<=', now()->addDays($dias)->toDateString());
    }

    public function scopeVencidos($q)
    {
        return $q->whereNotNull('vencimento_contrato')
                 ->whereDate('vencimento_contrato', '<', now()->toDateString());
    }

    // Quantos dias faltam (negativo se já venceu)
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->vencimento_contrato) return null;
        return Carbon::today()->diffInDays(Carbon::parse($this->vencimento_contrato), false);
    }

        /**
     * Percentual vigente com prioridade:
     * 1) contrato_cidade ATIVO para qualquer cidade do pedido (mais recente)
     * 2) contrato/aditivo ATIVO (global) mais recente
     * 3) campo percentual_vendas do cadastro
     *
     * @param \Illuminate\Support\Collection|array|null $cidadesIds
     * @return float
     */
    public function percentualVigenteParaCidades($cidadesIds = null): float
    {
        $ids = collect($cidadesIds)->filter()->map(fn($v)=>(int)$v)->values();

        // 1) contrato por cidade
        if ($ids->isNotEmpty()) {
            $anexoCidade = $this->anexos()
                ->where('tipo', 'contrato_cidade')
                ->where('ativo', true)
                ->whereIn('cidade_id', $ids)
                ->orderByDesc('data_assinatura')
                ->orderByDesc('created_at')
                ->first();

            if ($anexoCidade && $anexoCidade->percentual_vendas !== null) {
                return (float) $anexoCidade->percentual_vendas;
            }
        }

        // 2) global ativo
        $anexoGlobal = $this->anexos()
            ->whereIn('tipo', ['contrato','aditivo'])
            ->where('ativo', true)
            ->orderByDesc('data_assinatura')
            ->orderByDesc('created_at')
            ->first();

        if ($anexoGlobal && $anexoGlobal->percentual_vendas !== null) {
            return (float) $anexoGlobal->percentual_vendas;
        }

        // 3) fallback
        return (float) ($this->percentual_vendas ?? 0);
    }

}
