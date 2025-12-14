<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function index(PedidoService $service)
    {
        $produtosComEstoqueBaixo = $service->produtosComEstoqueBaixo();
        $estoqueParaPedidosEmPotencial = $service->estoqueParaPedidosEmPotencial();
        $pedidos = $service->indexQuery()->paginate(10);

        return view('admin.pedidos.index', compact('pedidos','produtosComEstoqueBaixo','estoqueParaPedidosEmPotencial'));
    }

    public function create(PedidoService $service)
    {
        return view('admin.pedidos.create', $service->createPayload());
    }

    public function store(Request $request, PedidoService $service)
    {
        try {
            $service->store($request);
            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Erro ao criar pedido: '.$e->getMessage()])->withInput();
        }
    }

    public function show(Pedido $pedido, PedidoService $service)
    {
        return view('admin.pedidos.show', $service->showPayload($pedido));
    }

    public function exportar(Pedido $pedido, string $tipo, PedidoService $service)
    {
        return $service->exportarPdf($pedido, $tipo);
    }

    public function edit(Pedido $pedido, PedidoService $service)
    {
        if ($pedido->status === 'finalizado') {
            return redirect()->route('admin.pedidos.show', $pedido)
                ->with(['error' => 'Pedido finalizado não pode mais ser editado.']);
        }

        return view('admin.pedidos.edit', $service->editPayload($pedido));
    }

    public function update(Pedido $pedido, Request $request, PedidoService $service)
    {
            try {
                $service->update($pedido, $request);
                return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            } catch (\Throwable $e) {
                return back()->withErrors(['error' => 'Erro ao atualizar: '.$e->getMessage()])->withInput();
            }
        }
    }
