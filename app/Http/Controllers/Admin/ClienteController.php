<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private ClienteService $service)
    {
    }

    public function index()
    {
        $clientes = $this->service->paginateIndex(10);

        return view('admin.clientes.index', compact('clientes'));
    }

    public function create(Request $request)
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $this->service->create($request);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        return view('admin.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->service->update($request, $cliente);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Cliente $cliente)
    {
        $this->service->delete($cliente);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente excluído com sucesso!');
    }
}
