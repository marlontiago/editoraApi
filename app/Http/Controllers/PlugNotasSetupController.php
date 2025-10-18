<?php
// app/Http/Controllers/PlugNotasSetupController.php
namespace App\Http\Controllers;

use App\Services\PlugNotasService;

class PlugNotasSetupController extends Controller
{
    public function empresa(PlugNotasService $pn)
    {
        $onlyDigits = fn ($v) => preg_replace('/\D+/', '', (string) $v);

        $cnpj = $onlyDigits(env('EMPRESA_CNPJ', ''));
        // IBGE de Maringá/PR = 4115200 (ajuste para sua cidade, se quiser)
        $codigoCidade = 4115200;

        $empresa = [
            'cpfCnpj'        => $cnpj,
            'razaoSocial'    => env('EMPRESA_RAZAO', 'Emitente Exemplo'),
            'nomeFantasia'   => env('EMPRESA_RAZAO', 'Emitente Exemplo'),
            'email'          => 'fiscal@editoralt.com.br',
            'telefone'       => '44999999999',
            'simplesNacional'=> false, // se a empresa for do Simples, troque para true

            'endereco'       => [
                'logradouro'   => 'Rua Um',
                'numero'       => '123',
                'bairro'       => 'Centro',
                'cep'          => '87000000',
                'cidade'       => 'Maringa',
                'estado'       => 'PR',        // <<< CAMPO EXIGIDO
                'codigoCidade' => $codigoCidade // <<< CAMPO EXIGIDO (IBGE)
            ],

            // Habilite NFe (modelo 55)
            'nfe' => [
                'habilitado' => true,
                'config'     => [
                    'serie' => 1,
                ],
            ],

            // Se fosse NFS-e:
            // 'nfse' => ['habilitado' => true, 'config' => []],
        ];

        $res = $pn->createCompany($empresa);
        return response()->json($res);
    }
}
