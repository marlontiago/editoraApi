<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\Colecao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProdutoService
{
    /** Helper: normaliza números pt-BR "1.234,56" -> "1234.56" */
    public function toFloat($v)
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        if ($v === '') return null;

        if (is_numeric($v)) return $v;

        if (strpos($v, ',') !== false) {
            $v = str_replace(['.', ' '], '', $v);
            $v = str_replace(',', '.', $v);
            return $v;
        }

        $v = str_replace(' ', '', $v);
        $v = str_replace(',', '.', $v);

        return $v;
    }

    public function normalize(Request $request): void
    {
        $request->merge([
            'preco' => $this->toFloat($request->input('preco')),
            'peso'  => $this->toFloat($request->input('peso')),
        ]);
    }

    /** Regras compartilhadas (web e api) */
    public function rules(Request $request, ?Produto $produto = null): array
    {
        $isUpdate = $produto !== null;

        return [
            'codigo' => [
                'required',
                'integer',
                'min:1',
                $isUpdate
                    ? Rule::unique('produtos', 'codigo')->ignore($produto->id)
                    : 'unique:produtos,codigo',
            ],

            'titulo' => ['required','string','max:255'],

            'isbn' => [
                'nullable','string','max:17',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') return;

                    $digits = preg_replace('/\D/', '', $value);

                    if (strlen($digits) !== 13) {
                        $fail('O ISBN deve conter exatamente 13 dígitos (com ou sem traços).');
                    }
                    if (!preg_match('/^[0-9-]+$/', $value)) {
                        $fail('Use apenas números e traços no ISBN.');
                    }
                },
            ],

            'autores' => ['nullable','string','max:255'],
            'edicao' => ['nullable','string','max:50'],
            'ano' => ['nullable','integer','min:1900','max:'.date('Y')],
            'numero_paginas' => ['nullable','integer','min:1'],
            'quantidade_por_caixa' => ['nullable','integer','min:0'],
            'peso' => ['nullable','numeric','min:0'],

            'ano_escolar' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(config('ano_escolar.opcoes')),
            ],

            'colecao_id' => ['nullable','exists:colecoes,id'],
            'descricao' => ['nullable','string'],
            'preco' => ['nullable','numeric','min:0'],
            'quantidade_estoque' => ['nullable','integer','min:0'],
            'imagem' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ];
    }

    /** Query base do index (com filtro/ordenação/paginação) */
    public function indexQuery(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Produto::query()
            ->with('colecao')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($qbuilder) use ($q) {
                    // Obs: seu banco está com ILIKE (pgsql). Se estiver em mysql, troca por like + lower.
                    $qbuilder
                        ->where('titulo', 'ilike', "%{$q}%")
                        ->orWhere('autores', 'ilike', "%{$q}%")
                        ->orWhereHas('colecao', fn($cq) => $cq->where('nome', 'ilike', "%{$q}%"));
                });
            });

        $sort = $request->get('sort', 'titulo');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['titulo','preco','quantidade_estoque','ano'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'titulo';

        $query->orderBy($sort, $dir);

        return $query;
    }

    public function estoqueBaixo()
    {
        return Produto::where(function ($q) {
                $q->whereNull('quantidade_estoque')
                  ->orWhere('quantidade_estoque', '<=', 100);
            })
            ->orderByRaw('COALESCE(quantidade_estoque, 0) ASC')
            ->get();
    }

    public function estoqueParaPedidosEmPotencial()
    {
        return DB::table('pedido_produto as pp')
            ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
            ->join('produtos as pr', 'pr.id', '=', 'pp.produto_id')
            ->where('pe.status', 'em_andamento')
            ->groupBy('pp.produto_id', 'pr.titulo', 'pr.quantidade_estoque')
            ->havingRaw('SUM(pp.quantidade) > COALESCE(pr.quantidade_estoque, 0)')
            ->get([
                'pp.produto_id',
                'pr.titulo',
                DB::raw('SUM(pp.quantidade) as qtd_em_pedidos'),
                DB::raw('COALESCE(pr.quantidade_estoque, 0) as quantidade_estoque'),
                DB::raw('SUM(pp.quantidade) - COALESCE(pr.quantidade_estoque, 0) AS excedente'),
            ]);
    }

    public function store(Request $request): Produto
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules($request));

        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        return Produto::create($dados);
    }

    public function update(Request $request, Produto $produto): Produto
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules($request, $produto));

        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
                Storage::disk('public')->delete($produto->imagem);
            }
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        } else {
            unset($dados['imagem']);
        }

        $produto->update($dados);

        return $produto->fresh();
    }

    public function destroy(Produto $produto): void
    {
        if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
            Storage::disk('public')->delete($produto->imagem);
        }
        $produto->delete();
    }

    /** Importação: movi seu método inteiro pra cá (mesma lógica) */
    public function import(Request $request): array
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $sheets = Excel::toArray([], $request->file('arquivo'));
        if (empty($sheets) || empty($sheets[0])) {
            return ['ok' => false, 'message' => 'A planilha está vazia ou não foi possível ler o arquivo.'];
        }

        $rows = $sheets[0];
        $rawHeaders = array_shift($rows);

        $headersMap = [];
        foreach ($rawHeaders as $i => $header) {
            $header = trim((string) $header);
            if ($header !== '') $headersMap[$header] = $i;
        }

        $map = [
            'CÓDIGO'               => 'codigo',
            'TÍTULO'               => 'titulo',
            'COLEÇÃO'              => 'colecao_nome',
            'ISBN'                 => 'isbn',
            'EDIÇÃO'               => 'edicao',
            'ANO'                  => 'ano',
            'Nº DE PÁGINAS'        => 'numero_paginas',
            'QUANTIDADE POR CAIXA' => 'quantidade_por_caixa',
            'PESO'                 => 'peso',
            'ANO ESCOLAR'          => 'ano_escolar_raw',
            'AUTOR(ES)'            => 'autores',
            'DESCRIÇÃO'            => 'descricao',
            'PREÇO'                => 'preco',
            'ESTOQUE'              => 'quantidade_estoque',
            'IMAGEM DO PRODUTO'    => 'imagem_raw',
        ];

        $indexes = [];
        foreach ($map as $headerName => $fieldName) {
            if (isset($headersMap[$headerName])) $indexes[$fieldName] = $headersMap[$headerName];
        }

        if (!isset($indexes['codigo']) || !isset($indexes['isbn'])) {
            return ['ok' => false, 'message' => 'A planilha deve ter as colunas "CÓDIGO" e "ISBN" no cabeçalho.'];
        }

        $criadas = 0; $atualizadas = 0; $ignoradas = 0;

        $colecoesMap = [];
        Colecao::select('id', 'nome')->chunk(100, function ($chunk) use (&$colecoesMap) {
            foreach ($chunk as $c) {
                $colecoesMap[mb_strtolower(trim($c->nome))] = $c->id;
            }
        });

        $isbnsVistosNoArquivo = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                $linha = [];
                foreach ($indexes as $fieldName => $idx) {
                    $linha[$fieldName] = $row[$idx] ?? null;
                }

                $codigoStr = trim((string)($linha['codigo'] ?? ''));
                if ($codigoStr === '') { $ignoradas++; continue; }
                $codigo = (int) $codigoStr;
                if (!$codigo) { $ignoradas++; continue; }

                $isbnStr = trim((string)($linha['isbn'] ?? ''));
                if ($isbnStr === '') { $ignoradas++; continue; }
                $isbnDigits = preg_replace('/\D/', '', $isbnStr);
                if ($isbnDigits === '') { $ignoradas++; continue; }

                if (isset($isbnsVistosNoArquivo[$isbnDigits])) { $ignoradas++; continue; }
                $isbnsVistosNoArquivo[$isbnDigits] = true;

                $colecaoId = null;
                if (!empty($linha['colecao_nome'])) {
                    $nomeColecao = trim((string)$linha['colecao_nome']);
                    $key = mb_strtolower($nomeColecao);
                    if (isset($colecoesMap[$key])) $colecaoId = $colecoesMap[$key];
                }

                $titulo = isset($linha['titulo']) ? trim((string)$linha['titulo']) : null;

                $preco = $this->toFloat($linha['preco'] ?? null);
                $peso  = $this->toFloat($linha['peso'] ?? null);

                $ano = (isset($linha['ano']) && $linha['ano'] !== '' && $linha['ano'] !== null) ? (int)$linha['ano'] : null;
                $numeroPaginas = (isset($linha['numero_paginas']) && $linha['numero_paginas'] !== '' && $linha['numero_paginas'] !== null) ? (int)$linha['numero_paginas'] : null;
                $quantidadeEstoque = (isset($linha['quantidade_estoque']) && $linha['quantidade_estoque'] !== '' && $linha['quantidade_estoque'] !== null) ? (int)$linha['quantidade_estoque'] : null;
                $quantidadePorCaixa = (isset($linha['quantidade_por_caixa']) && $linha['quantidade_por_caixa'] !== '' && $linha['quantidade_por_caixa'] !== null) ? (int)$linha['quantidade_por_caixa'] : 0;

                $anoEscolar = null;
                if (!empty($linha['ano_escolar_raw'])) {
                    $raw = trim(preg_replace('/\s+/', ' ', (string)$linha['ano_escolar_raw']));
                    $opcoes = config('ano_escolar.opcoes', []);

                    $mapOpcoes = [];
                    foreach ($opcoes as $opt) {
                        $mapOpcoes[mb_strtolower(trim(preg_replace('/\s+/', ' ', $opt)))] = $opt;
                    }

                    $keyRaw = mb_strtolower($raw);
                    $anoEscolar = $mapOpcoes[$keyRaw] ?? null;
                }

                $dados = [
                    'codigo'               => $codigo,
                    'titulo'               => $titulo,
                    'isbn'                 => $isbnStr,
                    'autores'              => $linha['autores'] ?? null,
                    'edicao'               => $linha['edicao'] ?? null,
                    'ano'                  => $ano,
                    'numero_paginas'       => $numeroPaginas,
                    'preco'                => $preco !== null ? (float)$preco : null,
                    'peso'                 => $peso !== null ? (float)$peso : null,
                    'quantidade_estoque'   => $quantidadeEstoque,
                    'quantidade_por_caixa' => $quantidadePorCaixa,
                    'ano_escolar'          => $anoEscolar,
                    'descricao'            => $linha['descricao'] ?? null,
                    'colecao_id'           => $colecaoId,
                ];

                foreach ($dados as $k => $v) {
                    if (is_string($v) && trim($v) === '') $dados[$k] = null;
                }

                $produtoExistente = Produto::whereRaw(
                    "regexp_replace(isbn, '\\D', '', 'g') = ?",
                    [$isbnDigits]
                )->first();

                if (!$produtoExistente) {
                    $produtoExistente = Produto::where('codigo', $codigo)->first();
                }

                if ($produtoExistente) {
                    $updates = [];

                    if (isset($indexes['titulo']) && $titulo) $updates['titulo'] = $titulo;
                    if (isset($indexes['autores']) && !empty($linha['autores'])) $updates['autores'] = trim((string)$linha['autores']);
                    if (isset($indexes['edicao']) && !empty($linha['edicao'])) $updates['edicao'] = trim((string)$linha['edicao']);
                    if (isset($indexes['ano']) && $ano !== null) $updates['ano'] = $ano;
                    if (isset($indexes['numero_paginas']) && $numeroPaginas !== null) $updates['numero_paginas'] = $numeroPaginas;
                    if (isset($indexes['preco']) && $preco !== null) $updates['preco'] = (float)$preco;
                    if (isset($indexes['peso']) && $peso !== null) $updates['peso'] = (float)$peso;
                    if (isset($indexes['quantidade_estoque']) && $quantidadeEstoque !== null) $updates['quantidade_estoque'] = $quantidadeEstoque;
                    if (isset($indexes['quantidade_por_caixa']) && $quantidadePorCaixa !== null) $updates['quantidade_por_caixa'] = $quantidadePorCaixa;
                    if (isset($indexes['descricao']) && !empty($linha['descricao'])) $updates['descricao'] = trim((string)$linha['descricao']);
                    if (isset($indexes['colecao_nome']) && $colecaoId !== null) $updates['colecao_id'] = $colecaoId;
                    if (isset($indexes['ano_escolar_raw']) && $anoEscolar !== null) $updates['ano_escolar'] = $anoEscolar;

                    if (!empty($updates)) {
                        $produtoExistente->update($updates);
                        $atualizadas++;
                    } else {
                        $ignoradas++;
                    }

                    continue;
                }

                Produto::create($dados);
                $criadas++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return ['ok' => false, 'message' => 'Erro ao importar planilha: '.$e->getMessage()];
        }

        return [
            'ok' => true,
            'criadas' => $criadas,
            'atualizadas' => $atualizadas,
            'ignoradas' => $ignoradas,
            'message' => "Importação concluída. Criados: {$criadas}, Atualizados: {$atualizadas}, Ignorados: {$ignoradas}.",
        ];
    }
}
