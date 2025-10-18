<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class PlugNotasService
{
    private string $base;
    private string $key;

    public function __construct()
    {
        $cfg = config('services.plugnotas', []);
        $this->base = rtrim($cfg['base_url'] ?? '', '/');
        $this->key  = $cfg['api_key'] ?? '';
    }

    private function client()
    {
        return Http::withHeaders(['x-api-key' => $this->key])
                   ->baseUrl($this->base)
                   ->acceptJson();
    }

    /* --- Empresa (sandbox/produção) --- */
    public function createCompany(array $data): Response
    {
        return $this->client()->post('/empresa', $data);
    }

    public function uploadCertificate(string $pfxPath, string $senha): Response
    {
        return $this->client()
            ->attach('arquivo', file_get_contents($pfxPath), basename($pfxPath))
            ->attach('senha', $senha)
            ->post('/certificado');
    }

    /* --- NF-e --- */
    public function sendNFe(array $payload): Response
    {
        return $this->client()->post('/nfe', [$payload]);
    }

    public function resumoNFe(string $idOrChave): Response
    {
        return $this->client()->get("/nfe/{$idOrChave}/resumo");
    }

    public function pdfNFe(string $id)
    {
        return $this->client()->get("/nfe/{$id}/pdf");
    }

    public function xmlNFe(string $id)
    {
        return $this->client()->get("/nfe/{$id}/xml");
    }

    public static function makeIdIntegracao(string $prefix = 'NF'): string
    {
        return $prefix.'-'.Str::upper(Str::random(10));
    }
}
