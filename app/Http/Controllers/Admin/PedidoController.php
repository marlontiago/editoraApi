<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\City;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['cidades', 'gestor', 'distribuidor.user'])->latest()->get();
        return view('admin.pedidos.index', compact('pedidos'));
    }

    public function create()
    {
        $gestores = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos = Produto::orderBy('nome')->get();
        $cidades = City::orderBy('name')->get();        

        return view('admin.pedidos.create', compact('produtos', 'cidades', 'gestores', 'distribuidores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|date',
            'produtos' => 'required|array|min:1',
            'produtos.*.id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
            'desconto' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::create([
                'gestor_id' => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'data' => $request->data,
                'status' => 'em_andamento',
                'desconto' => $request->desconto ?? 0,
            ]);

            // Relacionar cidades (do gestor e distribuidor)
            $cidadesGestor = $request->gestor_id
                ? Gestor::with('cities')->find($request->gestor_id)?->cities ?? collect()
                : collect();

            $cidadesDistribuidor = $request->distribuidor_id
                ? Distribuidor::with('cities')->find($request->distribuidor_id)?->cities ?? collect()
                : collect();

            $todasCidades = $cidadesGestor->merge($cidadesDistribuidor)->unique('id');
            $pedido->cidades()->sync($todasCidades->pluck('id'));

            $pesoTotal = 0;
            $totalCaixas = 0;
            $valorBruto = 0;
            $valorComDesconto = 0;
            $desconto = is_numeric($request->desconto) ? floatval($request->desconto) : 0;

            foreach ($validated['produtos'] as $produtoData) {
                //  trava a linha do produto
                $produto = Produto::whereKey($produtoData['id'])->lockForUpdate()->firstOrFail();
                $quantidade = (int) $produtoData['quantidade'];

                //  validação de estoque
                $disponivel = (int) $produto->quantidade_estoque;
                if ($disponivel < $quantidade) {
                    throw new \RuntimeException(
                        "Estoque insuficiente para o produto {$produto->nome}. Disponível: {$disponivel}, solicitado: {$quantidade}"
                    );
                }

                // cálculos
                $precoUnitario = (float) $produto->preco;
                $subtotalBruto = $precoUnitario * $quantidade;

                $precoComDesconto = $precoUnitario * (1 - ($desconto / 100));
                $subtotalComDesconto = $precoComDesconto * $quantidade;

                $pesoTotalProduto = (float) ($produto->peso ?? 0) * $quantidade;
                $porCaixa = max(1, (int) $produto->quantidade_por_caixa);
                $caixas = (int) ceil($quantidade / $porCaixa);

                // pivot
                $pedido->produtos()->attach($produto->id, [
                    'quantidade'           => $quantidade,
                    'preco_unitario'       => $precoUnitario,
                    'desconto_aplicado'    => $desconto,
                    'subtotal'             => $subtotalComDesconto,
                    'peso_total_produto'   => $pesoTotalProduto,
                    'caixas'               => $caixas,
                ]);

                //  debita estoque
                $produto->decrement('quantidade_estoque', $quantidade);

                // acumuladores
                $pesoTotal        += $pesoTotalProduto;
                $totalCaixas      += $caixas;
                $valorBruto       += $subtotalBruto;
                $valorComDesconto += $subtotalComDesconto;
            }

            $pedido->update([
                'peso_total' => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto' => $valorBruto,
                'valor_total' => $valorComDesconto,
            ]);

            $pedido->registrarLog('Pedido criado', 'Pedido criado com desconto de ' . ($request->desconto ?? 0) . '%');

            DB::commit();

            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao criar pedido: ' . $e->getMessage()]);
        }
    }

    public function show(Pedido $pedido)
    {
        $pedido->load(['cidades', 'gestor', 'distribuidor.user', 'produtos']);
        return view('admin.pedidos.show', compact('pedido'));
    }

    public function exportar(Pedido $pedido, string $tipo)
    {
        $pedido->load(['produtos', 'cidades', 'gestor', 'distribuidor.user']);

        if (!in_array($tipo, ['relatorio', 'orcamento'])) abort(404);

        $view = $tipo === 'relatorio'
            ? 'admin.pedidos.pdf.relatorio'
            : 'admin.pedidos.pdf.orcamento';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'pedido' => $pedido,
            'tipo'   => $tipo,
        ])
        ->setPaper('a4')
        ->setOptions([
            'defaultFont'     => 'DejaVu Sans',
            'isRemoteEnabled' => true, // permite carregar imagens via caminho/URL
        ]);

        return $pdf->download("pedido-{$pedido->id}-{$tipo}.pdf");
    }

    public function edit(Pedido $pedido)
    {

        if ($pedido->status === 'finalizado') {
        return redirect()
            ->route('admin.pedidos.show', $pedido)
            ->with(['error' => 'Pedido finalizado não pode mais ser editado.']);
    }

        $pedido->load(['cidades', 'produtos', 'gestor', 'distribuidor.user']);

        $gestores = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos = Produto::orderBy('nome')->get();
        $cidades = City::orderBy('name')->get();

        // Mapa atual de produtos (id => quantidade) para preencher formulário
        $itensAtuais = $pedido->produtos->mapWithKeys(fn($p) => [
            $p->id => [
                'quantidade' => (int)$p->pivot->quantidade,
                'preco_unitario' => (float)$p->pivot->preco_unitario,
            ]
        ])->toArray();

        return view('admin.pedidos.edit', compact(
            'pedido','gestores','distribuidores','produtos','cidades','itensAtuais'
        ));
    }

    public function update(Pedido $pedido, Request $request)
    {
        
        // Verifica e bloqueia se o status for finalizado
        if ($pedido->status === 'finalizado') {
            return back()->with('error', 'Não é mais possível editar: este pedido já foi finalizado.');
        }

        // Se alguma linha tiver valor null ou zero ele remove e passa apenas as linhas com todos os campos
        $produtosLimpos = collect($request->input('produtos', []))
            ->filter(fn ($row) => isset($row['id'], $row['quantidade']) && $row['id'] !== '' && (int)$row['quantidade'] > 0)
            ->values()->all();
        $request->merge(['produtos' => $produtosLimpos]);

        // Valida requisição
        $validated = $request->validate([
            'data' => 'required|date',
            'gestor_id' => 'nullable|exists:gestores,id',
            'distribuidor_id' => 'nullable|exists:distribuidores,id',
            'desconto' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:em_andamento,finalizado,cancelado',
            'cidades' => 'array',
            'cidades.*' => 'exists:cities,id',
            'produtos' => 'required|array|min:1',
            'produtos.*.id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
        ]);
    
        // Inicia transação no banco de dados
        DB::beginTransaction();
        try {
            // Carrega o estado atual
            $pedido->load(['produtos', 'cidades']);

            // Aqui cria um snapshot antes pra comparar com o próximo snapshot pra ver o que foi alterado
            $antes = [
                'campos' => [
                    'data' => $pedido->data,
                    'gestor_id' => $pedido->gestor_id,
                    'distribuidor_id' => $pedido->distribuidor_id,
                    'desconto' => (float)$pedido->desconto,
                    'status' => $pedido->status,
                ],
                'cidades' => $pedido->cidades->pluck('id')->sort()->values()->all(),
                'itens' => $pedido->produtos->mapWithKeys(fn($p) => [$p->id => (int)$p->pivot->quantidade])->toArray(),
            ];

            $novoStatus = $validated['status'];

            // Aqui caso o campo status seja alterado pra cancelado ele repôe no estoque
            // todos aqueles itens daquele pedido  
            if ($antes['campos']['status'] !== 'cancelado' && $novoStatus === 'cancelado') {
                $ids = array_keys($antes['itens']);
                if (!empty($ids)) {
                    // Trava e busca produtos
                    $produtosLock = Produto::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                    // Aqui mostra detalhes do que foi inserido ou removido
                    $detalhesReposicao = [];
                    foreach ($antes['itens'] as $pid => $qtd) {
                        if ($qtd <= 0) continue;
                        /** @var \App\Models\Produto|null $prod */
                        $prod = $produtosLock[$pid] ?? null;
                        if ($prod) {
                            $prod->increment('quantidade_estoque', $qtd);
                            $detalhesReposicao[] = "{$prod->nome} (+{$qtd} un)";
                        }
                    }
                    // Ele mantém como histórico o que foi inserido ou removido,
                    // a única mudança no banco é o status.
                }

                // Aqui atualiza os campos simples sem mexer nos itens do pedido
                $pedido->update([
                    'data' => $validated['data'],
                    'gestor_id' => $request->gestor_id,
                    'distribuidor_id' => $request->distribuidor_id,
                    'desconto' => $request->desconto ?? 0,
                    'status' => 'cancelado',
                ]);
                $pedido->cidades()->sync(collect($request->cidades ?? [])->unique()->values());

                // Mostra a log na view sobre o que foi alterado no pedido
                $pedido->registrarLog(
                    'Pedido cancelado',
                    !empty($detalhesReposicao)
                        ? 'Estoque devolvido: ' . implode(', ', $detalhesReposicao)
                        : 'Pedido cancelado sem itens.',
                    ['antes' => $antes]
                );

                DB::commit();
                return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido cancelado e estoque devolvido.');
            }
            // FIM DO CANCELAMENTO 

            // Aqui segue o fluxo normal caso o status seja "em_andamento" ou "finalizado"

            // Atualiza os campos simples e cidades
            $pedido->update([
                'data' => $validated['data'],
                'gestor_id' => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'desconto' => $request->desconto ?? 0,
                'status' => $novoStatus,
            ]);
            $pedido->cidades()->sync(collect($request->cidades ?? [])->unique()->values());

            // Aqui é o snapshot depois que são os dados vindos do form, que será comparado
            // com o snap antes criado acima antes da verificação de status "cancelado".
            $depoisItens = collect($validated['produtos'])->mapWithKeys(fn($it) => [(int)$it['id'] => (int)$it['quantidade']])->toArray();

            //Aqui pega apenas os itens do snapshot, salvando seus IDs nas variaveis $antesIds e $depoisIds.
            $antesItens = $antes['itens'];
            $antesIds   = array_keys($antesItens);
            $depoisIds  = array_keys($depoisItens);

            // Aqui usa diff pra comparar os arrays, em $adicionados mostra os ids que nao existiam antes,
            // em $removidos mostra os IDs que sumiram e em $comuns mostra os IDs que permanecem.
            $adicionados = array_values(array_diff($depoisIds, $antesIds));
            $removidos   = array_values(array_diff($antesIds, $depoisIds));
            $comuns      = array_values(array_intersect($antesIds, $depoisIds));

            // Em $envolvidos é feito o merge pra unir os 2 arrays e fazer a verificação se existe
            // estoque suficiente pros produtos que estão sendo alterados.
            $envolvidos = array_values(array_unique(array_merge($antesIds, $depoisIds)));
            $produtosLock = Produto::whereIn('id', $envolvidos)->lockForUpdate()->get()->keyBy('id');

            //Aqui para cada produto ele verifica as diferenças mas sem mexer no estoque ainda.
            foreach ($envolvidos as $pid) {
                $qAntes  = (int)($antesItens[$pid]  ?? 0);
                $qDepois = (int)($depoisItens[$pid] ?? 0);
                $delta   = $qDepois - $qAntes;
                //Se existir alguma diferença ela será maior que zero, então executará o if abaixo.                
                if ($delta > 0) {
                    $p = $produtosLock[$pid] ?? null;                    
                    if (!$p) throw new \RuntimeException("Produto {$pid} não encontrado.");
                    //Aqui ele pega o estoque disponível e faz a verificação pra ver se a diferença não é maior que o estoque.
                    $disp = (int)$p->quantidade_estoque;                    
                    if ($disp < $delta) {
                        throw new \RuntimeException("Estoque insuficiente para o produto {$p->nome}. Disponível: {$disp}, necessário: {$delta}");
                    }
                }
            }

            // Aqui ele aplica a diferença após validada em cima.
            foreach ($envolvidos as $pid) {
                $qAntes  = (int)($antesItens[$pid]  ?? 0);
                $qDepois = (int)($depoisItens[$pid] ?? 0);
                $delta   = $qDepois - $qAntes;

                if ($delta === 0) continue;
                if ($delta > 0) {
                    Produto::whereKey($pid)->decrement('quantidade_estoque', $delta);
                } else {
                    Produto::whereKey($pid)->increment('quantidade_estoque', -$delta);
                }
            }

            // Aqui ele inicializa o desconto com o que vier na requisição transformando em float ou zero caso não venha nada.
            $desconto = is_numeric($request->desconto) ? (float)$request->desconto : 0.0;
            // Aqui inicializa com zero as demais variáveis.
            $pesoTotal = 0; $totalCaixas = 0; $valorBruto = 0; $valorComDesconto = 0;
            $sync = [];

            // Pra cada item vindo do form($depoisItens) ele itera e atualiza os produtos.
            foreach ($depoisItens as $pid => $qtd) {
                $produto = $produtosLock[$pid] ?? Produto::findOrFail($pid);

                $precoUnit = (float)$produto->preco;
                $subBruto  = $precoUnit * $qtd;
                $precoDesc = $precoUnit * (1 - ($desconto / 100));
                $subDesc   = $precoDesc * $qtd;

                $pesoTotalProduto = (float)($produto->peso ?? 0) * $qtd;
                $caixas = (int)ceil($qtd / max(1, (int)$produto->quantidade_por_caixa));

                $sync[$pid] = [
                    'quantidade' => $qtd,
                    'preco_unitario' => $precoUnit,
                    'desconto_aplicado' => $desconto,
                    'subtotal' => $subDesc,
                    'peso_total_produto' => $pesoTotalProduto,
                    'caixas' => $caixas,
                ];

                $pesoTotal        += $pesoTotalProduto;
                $totalCaixas      += $caixas;
                $valorBruto       += $subBruto;
                $valorComDesconto += $subDesc;
            }

            $pedido->produtos()->sync($sync);

            $pedido->update([
                'peso_total'   => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto'  => $valorBruto,
                'valor_total'  => $valorComDesconto,
            ]);

            // Aqui ele cria o snapshot depois para registro de log.
            $pedido->load(['produtos','cidades']);
            $depois = [
                'campos' => [
                    'data' => $pedido->data,
                    'gestor_id' => $pedido->gestor_id,
                    'distribuidor_id' => $pedido->distribuidor_id,
                    'desconto' => (float)$pedido->desconto,
                    'status' => $pedido->status,
                ],
                'cidades' => $pedido->cidades->pluck('id')->sort()->values()->all(),
                'itens' => $pedido->produtos->mapWithKeys(fn($p) => [$p->id => (int)$p->pivot->quantidade])->toArray(),
            ];

            // Aqui ele gera as mensagens do que foi alterado para disponibilizar na view.
            $mensagens = [];
            foreach ($antes['campos'] as $k => $vAntes) {
                $vDepois = $depois['campos'][$k];
                if ((string)$vAntes !== (string)$vDepois) {
                    $mensagens[] = ucfirst($k) . " alterado: '{$vAntes}' → '{$vDepois}'";
                }
            }
            // Se o diff feito la em cima verificou se algo foi adicionado ele executa esse if.
            if ($adicionados) {
                $nomes = [];
                foreach ($adicionados as $pid) {
                    $p = $produtosLock[$pid] ?? Produto::find($pid);
                    if ($p) $nomes[] = "{$p->nome} ({$depoisItens[$pid]} un)";
                }
                if ($nomes) $mensagens[] = 'Produtos adicionados: ' . implode(', ', $nomes);
            }
            // Se o diff feito la em cima verificou se algo foi removido ele executa esse if.
            if ($removidos) {
                $nomes = [];
                foreach ($removidos as $pid) {
                    $p = $produtosLock[$pid] ?? Produto::find($pid);
                    if ($p) $nomes[] = "{$p->nome} ({$antesItens[$pid]} un)";
                }
                if ($nomes) $mensagens[] = 'Produtos removidos: ' . implode(', ', $nomes);
            }
            // Se o diff feito la em cima verificou se algo foi alterado ele executa esse if.
            $alterados = [];
            foreach ($comuns as $pid) {
                $a = $antesItens[$pid] ?? 0;
                $d = $depoisItens[$pid] ?? 0;
                if ($a !== $d) $alterados[] = $pid;
            }
            if ($alterados) {
                $nomes = [];
                foreach ($alterados as $pid) {
                    $p = $produtosLock[$pid] ?? Produto::find($pid);
                    if ($p) $nomes[] = "{$p->nome} ({$antesItens[$pid]} → {$depoisItens[$pid]} un)";
                }
                if ($nomes) $mensagens[] = 'Quantidades alteradas: ' . implode(', ', $nomes);
            }

            // Aqui ele verifica apenas o status do pedido com match.
            $acaoLog = match ($pedido->status) {
                'finalizado' => 'Pedido finalizado',
                'cancelado'  => 'Pedido cancelado', // (não deve cair aqui porque tratamos acima)
                default      => 'Pedido atualizado',
            };

            // Aqui ele faz o registro com a função do model Pedido registrarLog.
            $pedido->registrarLog(
                $acaoLog,
                $mensagens ? implode(' | ', $mensagens) : 'Atualização sem mudanças relevantes',
                ['antes' => $antes, 'depois' => $depois]
            );

            DB::commit();
            return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
        }
    }

}
