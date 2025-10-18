<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GestorAnexo extends Model
{
    use HasFactory;

    /** Se sua tabela for outra (ex.: "anexos"), ajuste aqui */
    protected $table = 'gestor_anexos';

    /** Campos liberados para atribuição em massa */
    protected $fillable = [
        'gestor_id',
        'tipo',                // contrato | aditivo | contrato_cidade | outro
        'descricao',
        'percentual_vendas',   // decimal(5,2) ou float
        'data_assinatura',     // date
        'data_vencimento',     // date
        'assinado',            // bool
        'ativo',               // bool
        'cidade_id',           // FK opcional p/ cities.id (quando tipo = contrato_cidade)
        'arquivo',             // path no storage (disk public)
    ];

    /** Casts para tipos corretos */
    protected $casts = [
        'assinado'         => 'boolean',
        'ativo'            => 'boolean',
        'data_assinatura'  => 'date',
        'data_vencimento'  => 'date',
        'percentual_vendas'=> 'float',
    ];

    /** Tipos permitidos (opcional, útil para validação/referência) */
    public const TIPOS = ['contrato','aditivo','contrato_cidade','outro'];

    /* ---------------- RELACIONAMENTOS ---------------- */

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function cidade()
    {
        // Ajuste o model se o seu for App\Models\City ou Cities
        return $this->belongsTo(City::class, 'cidade_id');
    }

    /* ---------------- ACCESSORS / HELPERS ---------------- */

    /** URL pública do PDF (se existir) */
    public function getArquivoUrlAttribute(): ?string
    {
        return $this->arquivo
            ? Storage::disk('public')->url($this->arquivo)
            : null;
    }

    /** Label bonito para o tipo */
    public function getTipoLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', (string)$this->tipo));
    }

    /** True quando o tipo é contrato por cidade */
    public function getIsContratoCidadeAttribute(): bool
    {
        return $this->tipo === 'contrato_cidade';
    }

    /** Percentual formatado PT-BR (ex.: 12,50%) */
    public function getPercentualVendasFormatadoAttribute(): ?string
    {
        return is_null($this->percentual_vendas)
            ? null
            : number_format((float)$this->percentual_vendas, 2, ',', '.') . '%';
    }

    /* ---------------- MUTATORS ---------------- */

    /**
     * Aceita "12,34" ou "12.34" e normaliza para float.
     * Se vier vazio, seta como null.
     */
    public function setPercentualVendasAttribute($value): void
    {
        if ($value === '' || $value === null) {
            $this->attributes['percentual_vendas'] = null;
            return;
        }
        $norm = str_replace(',', '.', (string)$value);
        $this->attributes['percentual_vendas'] = (float)$norm;
    }

    /* ---------------- ESCOPOS ÚTEIS (opcionais) ---------------- */

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDoGestor($query, int $gestorId)
    {
        return $query->where('gestor_id', $gestorId);
    }
}
