<?php
namespace App\Support;

class CfopRules
{
    public static function isSimplesRemessa(?string $cfop): bool
    {
        $codes = config('cfop.simples_remessa', []);
        return $cfop && in_array($cfop, $codes, true);
    }

    public static function isBonificacao(?string $cfop): bool
    {
        $codes = config('cfop.bonificacao', []);
        return $cfop && in_array($cfop, $codes, true);
    }

    /**
     * Regras pedidas pelo cliente:
     * - Simples remessa: NÃO altera estoque; NÃO entra no relatório financeiro; entra no geral.
     * - Bonificação/brinde: ALTERA estoque; NÃO entra no relatório financeiro; entra no geral.
     */
    public static function alteraEstoque(?string $cfop): bool
    {
        if (self::isSimplesRemessa($cfop)) return false;
        if (self::isBonificacao($cfop))     return true;
        // default (para outros CFOPs reais): geralmente altera estoque
        return true;
    }

    public static function entraNoFinanceiro(?string $cfop): bool
    {
        // ambos NÃO entram no financeiro
        if (self::isSimplesRemessa($cfop)) return false;
        if (self::isBonificacao($cfop))     return false;
        // default: entra
        return true;
    }

    public static function entraNoRelatorioGeral(?string $cfop): bool
    {
        // os dois devem entrar no RELATÓRIO GERAL
        return true;
    }
}
