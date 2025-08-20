<?php

namespace App\Support;

class Formatters
{
    /**
     * Formata o ISBN de um produto.
     *
     * @param string|null $isbn
     * @return string
     */
    public static function formatIsbn(?string $isbn): string
    {
        if (is_null($isbn)) {
            return '—';
        }

        $isbn = preg_replace('/\D/', '', $isbn);

        if (strlen($isbn) === 13) {
            return preg_replace('/^(\d{3})(\d{2})(\d{5})(\d{2})(\d{1})$/', '$1-$2-$3-$4-$5', $isbn);
        }

        return $isbn; // Retorna o ISBN original se não for 13 dígitos
    }

    public static function formatCpf(?string $cpf): string
    {
        if (is_null($cpf)) {
            return '—';
        }

        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) === 11) {
            return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
        }

        return $cpf; // Retorna o CPF original se não for 11 dígitos
    }

    public static function formatCnpj(?string $cnpj): string
    {
        if (is_null($cnpj)) {
            return '—';
        }

        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
        }

        return $cnpj; // Retorna o CNPJ original se não for 14 dígitos
    }

    public static function formatTelefone(?string $telefone): string
    {
        if (is_null($telefone)) {
            return '—';
        }

        $telefone = preg_replace('/\D/', '', $telefone);

        if (strlen($telefone) === 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) === 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $telefone);
        }

        return $telefone; // Retorna o telefone original se não for 10 ou 11 dígitos
    }

    public static function formatRg(?string $rg): string
    {
        if (is_null($rg)) {
            return '—';
        }

        $rg = preg_replace('/\D/', '', $rg);

        if (strlen($rg) >= 7 && strlen($rg) <= 10) {
            return preg_replace('/^(\d{1,2})(\d{3})(\d{3})(\d{1,2})?$/', '$1.$2.$3-$4', $rg);
        }

        return $rg; // Retorna o RG original se não estiver no formato esperado
    }
}