<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Colecao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProdutoController extends Controller
{
    /** Helper: normaliza números pt-BR "1.234,56" -> "1234.56" */
    private function toFloat($v)
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        if ($v === '') return null; 

        // se já for um número (ex: 12.34) retorna direto (evita remoção indevida)
        if (is_numeric($v)) {
            return $v;
        }

        // Formato pt-BR: contém vírgula -> remover pontos de milhar e trocar vírgula por ponto
        if (strpos($v, ',') !== false) {
            $v = str_replace(['.', ' '], '', $v); 
            $v = str_replace(',', '.', $v);       
            return $v;
        }

        // Caso genérico: remover espaços e tentar trocar vírgula só por segurança
        $v = str_replace(' ', '', $v);
        $v = str_replace(',', '.', $v);

        return $v;
    }

    /** Helper: aplica normalizações no request */
    private function normalize(Request $request): void
    {
        $request->merge([
            //'isbn'  => preg_replace('/\D/', '', (string) $request->input('isbn')),
            'preco' => $this->toFloat($request->input('preco')),
            'peso'  => $this->toFloat($request->input('peso')),
        ]);
    }

    /** Regras de validação compartilhadas */
    private function rules(bool $isUpdate = false): array
    {
        return [
            'codigo' => [
            'required',
            'integer',
            'min:1',
            $isUpdate
                ? Rule::unique('produtos', 'codigo')->ignore(request()->route('produto'))
                : 'unique:produtos,codigo'
        ],
            'titulo' => ['required','string','max:255'],
            'isbn' => [
                'nullable','string','max:17', // cabe "978-65-88702-17-8"
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


    public function index(Request $request)
    {
        $produtosComEstoqueBaixo = Produto::where(function ($q) {
                $q->whereNull('quantidade_estoque')
                ->orWhere('quantidade_estoque', '<=', 100);
            })
            // se quiser ordenar mostrando NULL (0) primeiro:
            ->orderByRaw('COALESCE(quantidade_estoque, 0) ASC')
            ->get();

        $estoqueParaPedidosEmPotencial = DB::table('pedido_produto as pp')
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

        $q = trim((string) $request->get('q',''));

        $query = Produto::query()
            ->with('colecao')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($qbuilder) use ($q) {
                    $qbuilder
                        ->where('titulo', 'ilike', "%{$q}%")
                        ->orWhere('autores', 'ilike', "%{$q}%")
                        ->orWhereHas('colecao', fn($cq) => $cq->where('nome', 'ilike', "%{$q}%"));
                });
            });

        // Ordenação (?sort=titulo|preco|quantidade_estoque|ano&dir=asc|desc)
        $sort = $request->get('sort', 'titulo');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['titulo','preco','quantidade_estoque','ano'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'titulo';
        }
        $query->orderBy($sort, $dir);

        // Paginação
        $perPage = (int) $request->get('per_page', 15);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

        if ($request->wantsJson()) {
            return $query->paginate($perPage);
        }

        $produtos = $query->paginate($perPage)->withQueryString();

         $produtosParaColecao = Produto::orderBy('titulo', 'asc')
            ->get(['id', 'titulo', 'isbn', 'autores']);
            $colecoesResumo = \App\Models\Colecao::withCount('produtos')
        ->orderBy('nome')
        ->get(['id','codigo','nome']);

        return view('admin.produtos.index', compact(
            'produtos', 'q', 'sort', 'dir', 'produtosComEstoqueBaixo', 'estoqueParaPedidosEmPotencial', 'produtosParaColecao','colecoesResumo'
        ));
    }

    public function create()
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.create', compact('colecoes'));
    }

    public function store(Request $request)
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules());

        // Upload da imagem (se houver)
        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $produto = Produto::create($dados);

        if ($request->wantsJson()) {
            return response()->json($produto, 201);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.edit', compact('produto', 'colecoes'));
    }

    public function update(Request $request, Produto $produto)
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules(isUpdate: true));

        // Manter imagem atual caso não seja enviada
        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            // apaga a antiga se existir
            if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
                Storage::disk('public')->delete($produto->imagem);
            }
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        } else {
            unset($dados['imagem']);
        }

        $produto->update($dados);

        if ($request->wantsJson()) {
            return response()->json($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Produto $produto)
    {
        if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
            Storage::disk('public')->delete($produto->imagem);
        }

        $produto->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Produto removido com sucesso.']);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto removido com sucesso.');
    }

    public function show(Request $request, Produto $produto)
    {
        if ($request->wantsJson()) {
            return response()->json($produto);
        }

        return view('admin.produtos.show', compact('produto'));
    }

    public function import(Request $request)
{
    // 1) Valida o upload (xlsx/xls/csv)
    $request->validate([
        'arquivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // até 10MB
    ]);

    // 2) Lê o arquivo com Laravel Excel
    $sheets = Excel::toArray([], $request->file('arquivo'));

    if (empty($sheets) || empty($sheets[0])) {
        return back()->with('error', 'A planilha está vazia ou não foi possível ler o arquivo.');
    }

    // Só a primeira aba
    $rows = $sheets[0];

    // 3) Primeira linha = cabeçalho
    $rawHeaders = array_shift($rows); // remove a primeira linha

    // Cabeçalho original -> índice
    $headersMap = [];
    foreach ($rawHeaders as $i => $header) {
        $header = trim((string) $header);
        if ($header !== '') {
            $headersMap[$header] = $i;
        }
    }

    // Mapeamento dos nomes da SUA planilha -> campos internos
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
        'IMAGEM DO PRODUTO'    => 'imagem_raw', // ignorado
    ];

    // Descobre índice de cada coluna presente
    $indexes = [];
    foreach ($map as $headerName => $fieldName) {
        if (isset($headersMap[$headerName])) {
            $indexes[$fieldName] = $headersMap[$headerName];
        }
    }

    // Precisamos de CÓDIGO e ISBN no cabeçalho
    if (!isset($indexes['codigo']) || !isset($indexes['isbn'])) {
        return back()->with('error', 'A planilha deve ter as colunas "CÓDIGO" e "ISBN" no cabeçalho.');
    }

    $criadas     = 0;
    $atualizadas = 0;
    $ignoradas   = 0;

    // Mapa de coleções existentes: nome (lower/trim) => id
    $colecoesMap = [];
    Colecao::select('id', 'nome')->chunk(100, function ($chunk) use (&$colecoesMap) {
        foreach ($chunk as $c) {
            $chave = mb_strtolower(trim($c->nome));
            $colecoesMap[$chave] = $c->id;
        }
    });

    // Para evitar ISBN duplicado dentro do MESMO arquivo
    $isbnsVistosNoArquivo = [];

    DB::beginTransaction();
    try {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            // Monta linha[fieldName] => valor
            $linha = [];
            foreach ($indexes as $fieldName => $idx) {
                $linha[$fieldName] = $row[$idx] ?? null;
            }

            // ========== REGRAS BÁSICAS ==========
            // 1) CÓDIGO (obrigatório)
            $codigoRaw = $linha['codigo'] ?? null;
            $codigoStr = trim((string) $codigoRaw);

            if ($codigoStr === '') {
                $ignoradas++;
                continue;
            }

            $codigo = (int) $codigoStr;
            if (!$codigo) {
                $ignoradas++;
                continue;
            }

            // 2) ISBN (OBRIGATÓRIO pra importar)
            $isbnRaw = $linha['isbn'] ?? null;
            $isbnStr = trim((string) $isbnRaw);

            if ($isbnStr === '') {
                $ignoradas++;
                continue;
            }

            // Normaliza ISBN só pelos dígitos (pra comparar)
            $isbnDigits = preg_replace('/\D/', '', $isbnStr);
            if ($isbnDigits === '') {
                $ignoradas++;
                continue;
            }

            // 3) Evita duplicar dentro do próprio ARQUIVO
            if (isset($isbnsVistosNoArquivo[$isbnDigits])) {
                $ignoradas++;
                continue;
            }
            $isbnsVistosNoArquivo[$isbnDigits] = true;

            // ========== COLEÇÃO ==========
            $colecaoId = null;
            if (!empty($linha['colecao_nome'])) {
                $nomeColecao  = trim((string) $linha['colecao_nome']);
                $chaveColecao = mb_strtolower($nomeColecao);

                if (isset($colecoesMap[$chaveColecao])) {
                    $colecaoId = $colecoesMap[$chaveColecao];
                }
            }

            // ========== DEMAIS CAMPOS (podem ser vazios) ==========
            $tituloRaw = $linha['titulo'] ?? null;
            $titulo    = $tituloRaw !== null ? trim((string) $tituloRaw) : null;

            $preco = $this->toFloat($linha['preco'] ?? null);
            $peso  = $this->toFloat($linha['peso'] ?? null);

            $ano = null;
            if (isset($linha['ano']) && $linha['ano'] !== '' && $linha['ano'] !== null) {
                $ano = (int) $linha['ano'];
            }

            $numeroPaginas = null;
            if (isset($linha['numero_paginas']) && $linha['numero_paginas'] !== '' && $linha['numero_paginas'] !== null) {
                $numeroPaginas = (int) $linha['numero_paginas'];
            }

            $quantidadeEstoque = null;
            if (isset($linha['quantidade_estoque']) && $linha['quantidade_estoque'] !== '' && $linha['quantidade_estoque'] !== null) {
                $quantidadeEstoque = (int) $linha['quantidade_estoque'];
            }

            $quantidadePorCaixa = 0;
            if (isset($linha['quantidade_por_caixa']) && $linha['quantidade_por_caixa'] !== '' && $linha['quantidade_por_caixa'] !== null) {
                $quantidadePorCaixa = (int) $linha['quantidade_por_caixa'];
            }

            // Ano escolar: usa diretamente o valor da planilha, tentando casar com as opções do config
            $anoEscolar = null;
            if (!empty($linha['ano_escolar_raw'])) {
                $rawOriginal = (string) $linha['ano_escolar_raw'];

                // Normaliza espaços
                $raw = trim(preg_replace('/\s+/', ' ', $rawOriginal));

                // Lista oficial de opções
                $opcoesAnoEscolar = config('ano_escolar.opcoes', []);

                // Mapa normalizado (lowercase + espaços normalizados) -> texto exato da opção
                $mapOpcoes = [];
                foreach ($opcoesAnoEscolar as $opt) {
                    $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $opt)));
                    $mapOpcoes[$key] = $opt;
                }

                // Normaliza também o valor vindo da planilha
                $keyRaw = mb_strtolower($raw);

                if (isset($mapOpcoes[$keyRaw])) {
                    // Achou equivalência: grava exatamente como está no config
                    $anoEscolar = $mapOpcoes[$keyRaw];
                } else {
                    $anoEscolar = null;
                }
            }

            // Dados completos (para criação de NOVO produto)
            $dados = [
                'codigo'               => $codigo,
                'titulo'               => $titulo,
                'isbn'                 => $isbnStr,
                'autores'              => $linha['autores'] ?? null,
                'edicao'               => $linha['edicao'] ?? null,
                'ano'                  => $ano,
                'numero_paginas'       => $numeroPaginas,
                'preco'                => $preco !== null ? (float) $preco : null,
                'peso'                 => $peso !== null ? (float) $peso : null,
                'quantidade_estoque'   => $quantidadeEstoque,
                'quantidade_por_caixa' => $quantidadePorCaixa,
                'ano_escolar'          => $anoEscolar,
                'descricao'            => $linha['descricao'] ?? null,
                'colecao_id'           => $colecaoId,
            ];

            // Limpa strings vazias -> null
            foreach ($dados as $k => $v) {
                if (is_string($v) && trim($v) === '') {
                    $dados[$k] = null;
                }
            }

            // ========== VERIFICA SE JÁ EXISTE PRODUTO COM ESSE ISBN/CÓDIGO ==========
            $produtoExistente = Produto::whereRaw(
                "regexp_replace(isbn, '\\D', '', 'g') = ?",
                [$isbnDigits]
            )->first();

            if (!$produtoExistente) {
                $produtoExistente = Produto::where('codigo', $codigo)->first();
            }

            if ($produtoExistente) {
                // ======= MODO ATUALIZAÇÃO: só mexe nos campos que vieram na planilha =======
                $updates = [];

                // Se a coluna existe e veio valor, atualiza
                if (isset($indexes['titulo']) && $titulo !== null && $titulo !== '') {
                    $updates['titulo'] = $titulo;
                }

                if (isset($indexes['autores']) && !empty($linha['autores'])) {
                    $updates['autores'] = trim((string) $linha['autores']);
                }

                if (isset($indexes['edicao']) && !empty($linha['edicao'])) {
                    $updates['edicao'] = trim((string) $linha['edicao']);
                }

                if (isset($indexes['ano']) && $ano !== null) {
                    $updates['ano'] = $ano;
                }

                if (isset($indexes['numero_paginas']) && $numeroPaginas !== null) {
                    $updates['numero_paginas'] = $numeroPaginas;
                }

                if (isset($indexes['preco']) && $preco !== null) {
                    $updates['preco'] = (float) $preco;
                }

                if (isset($indexes['peso']) && $peso !== null) {
                    $updates['peso'] = (float) $peso;
                }

                if (isset($indexes['quantidade_estoque']) && $quantidadeEstoque !== null) {
                    $updates['quantidade_estoque'] = $quantidadeEstoque;
                }

                if (isset($indexes['quantidade_por_caixa']) && $quantidadePorCaixa !== null) {
                    $updates['quantidade_por_caixa'] = $quantidadePorCaixa;
                }

                if (isset($indexes['descricao']) && !empty($linha['descricao'])) {
                    $updates['descricao'] = trim((string) $linha['descricao']);
                }

                if (isset($indexes['colecao_nome']) && $colecaoId !== null) {
                    $updates['colecao_id'] = $colecaoId;
                }

                if (isset($indexes['ano_escolar_raw']) && $anoEscolar !== null) {
                    $updates['ano_escolar'] = $anoEscolar;
                }

                if (!empty($updates)) {
                    $produtoExistente->update($updates);
                    $atualizadas++;
                } else {
                    // Nada pra atualizar de fato
                    $ignoradas++;
                }

                continue;
            }

            // ======= MODO CRIAÇÃO: se não existe ainda, cria NOVO produto =======
            Produto::create($dados);
            $criadas++;
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        return back()->with('error', 'Erro ao importar planilha: ' . $e->getMessage());
    }

    $msg = "Importação concluída. Criados: {$criadas}, Atualizados: {$atualizadas}, Ignorados: {$ignoradas}.";
    return back()->with('success', $msg);
}


}
