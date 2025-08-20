<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::paginate(10); // Aqui você pode buscar todos os clientes ou aplicar filtros
        return view('admin.clientes.index', compact('clientes'));
    }

    public function create(Request $request)
    {                
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            
            'razao_social'               => 'required|string|max:255',
            'email'              => 'required|email|unique:clientes,email',
            'cnpj'               => 'nullable|string|max:18',
            'cpf'                => 'nullable|string|max:14',
            'rg'                 => 'nullable|string|max:20',
            'telefone'           => 'nullable|string|max:20',
            'endereco_completo'  => 'nullable|string|max:255',
        ]);

        Cliente::create([
            'user_id'           => auth()->id(), // Assumindo que o usuário autenticado é o responsável pelo cliente
            'razao_social'      => $validated['razao_social'],
            'email'             => $validated['email'],
            'cnpj'              => $validated['cnpj'] ?? null,
            'cpf'               => $validated['cpf'] ?? null,
            'rg'                => $validated['rg'] ?? null,
            'telefone'          => $validated['telefone'] ?? null,
            'endereco_completo' => $validated['endereco_completo'] ?? null,
            
        ]);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit($id)
    {
        // Lógica para exibir o formulário de edição de cliente
        return view('admin.clientes.edit', compact('id'));
    }

    public function update(Request $request, $id)
    {
        // Lógica para atualizar os dados do cliente
    }

    public function destroy($id)
    {
        // Lógica para excluir um cliente
    }
}
