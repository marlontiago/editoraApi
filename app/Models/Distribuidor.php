<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;
use Carbon\Carbon;

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

        // listas JSON
        'emails',
        'telefones',

        // Endereço principal
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',

        // Endereço secundário
        'endereco2',
        'numero2',
        'complemento2',
        'bairro2',
        'cidade2',
        'uf2',
        'cep2',

        // Comercial / Contrato
        'percentual_vendas',
        'percentual_vendas_base',
        'vencimento_contrato',
        'contrato_assinado',
    ];

    protected $casts = [
        'emails'               => 'array',
        'telefones'            => 'array',
        'vencimento_contrato'  => 'date',
        'contrato_assinado'    => 'boolean',
        'percentual_vendas'    => 'decimal:2',
    ];

    /* =======================
     |  Relacionamentos
     |=======================*/
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
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

    // >>> Removemos a relação de contatos, pois a seção foi eliminada do fluxo
    // public function contatos() { ... }

    /* =======================
     |  Helpers de formatação
     |=======================*/
    public function getCnpjFormatadoAttribute(): string
    {
        return Formatters::formatCnpj($this->cnpj);
    }

    // Telefones formatados (retorna array; se quiser string, faça implode onde exibir)
    public function getTelefonesFormatadosAttribute(): array
    {
        $tels = is_array($this->telefones) ? $this->telefones : [];
        return array_values(array_filter(array_map(function ($t) {
            $t = trim((string)$t);
            return $t !== '' ? Formatters::formatTelefone($t) : null;
        }, $tels)));
    }

    // Exibição simples de e-mail:
    // 1) se existir na lista de emails (primeiro da lista), usa ele
    // 2) senão, usa o e-mail do usuário relacionado (se não for placeholder)
    public function getEmailExibicaoAttribute(): string
    {
        $emailLista = '';
        if (is_array($this->emails) && count($this->emails) > 0) {
            $emailLista = trim((string)$this->emails[0]);
        }
        if ($emailLista !== '') {
            return $emailLista;
        }

        $emailUser = trim((string) ($this->user->email ?? ''));
        $isPlaceholder =
            $emailUser === '' ||
            preg_match('/^distribuidor\+.+@placeholder\.local$/i', $emailUser) ||
            str_ends_with($emailUser, '@placeholder.local');

        return $isPlaceholder ? 'Não informado' : $emailUser;
    }

    // compat: alguns lugares podem usar $distribuidor->estado_uf
    public function getEstadoUfAttribute(): ?string
    {
        return $this->uf;
    }

    /* =======================
     |  Scopes & Métricas
     |=======================*/
    public function scopeVencendoEmAte($q, int $dias = 30)
    {
        return $q->whereNotNull('vencimento_contrato')
                 ->whereDate('vencimento_contrato', '>=', now()->toDateString())
                 ->whereDate('vencimento_contrato', '<=', now()->addDays($dias)->toDateString());
    }

    // Contratos já vencidos
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
