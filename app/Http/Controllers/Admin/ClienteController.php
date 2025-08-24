<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('razao_social')->paginate(10);
        return view('admin.clientes.index', compact('clientes'));
    }

    public function create(Request $request)
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'razao_social'   => ['required','string','max:255'],
            'email'          => ['required','email','max:255','unique:clientes,email'],
            // Pelo menos um dos dois:
            'cnpj'           => ['nullable','string','max:18','required_without:cpf'],
            'cpf'            => ['nullable','string','max:14','required_without:cnpj'],
            'inscr_estadual' => ['nullable','string','max:30'],

            'telefone'       => ['nullable','string','max:20'],

            'endereco'       => ['nullable','string','max:255'],
            'numero'         => ['nullable','string','max:20'],
            'complemento'    => ['nullable','string','max:100'],
            'bairro'         => ['nullable','string','max:100'],
            'cidade'         => ['nullable','string','max:100'],
            'uf'             => ['nullable','string','size:2'],
            'cep'            => ['nullable','string','max:9'],
        ], [
            'cnpj.required_without' => 'Informe o CNPJ ou o CPF.',
            'cpf.required_without'  => 'Informe o CPF ou o CNPJ.',
        ]);

        if (!empty($validated['uf'])) {
            $validated['uf'] = strtoupper($validated['uf']);
        }

        Cliente::create([
            'user_id'        => auth()->id(),
            'razao_social'   => $validated['razao_social'],
            'email'          => $validated['email'],
            'cnpj'           => $validated['cnpj'] ?? null,
            'cpf'            => $validated['cpf'] ?? null,
            'inscr_estadual' => $validated['inscr_estadual'] ?? null,
            'telefone'       => $validated['telefone'] ?? null,
            'endereco'       => $validated['endereco'] ?? null,
            'numero'         => $validated['numero'] ?? null,
            'complemento'    => $validated['complemento'] ?? null,
            'bairro'         => $validated['bairro'] ?? null,
            'cidade'         => $validated['cidade'] ?? null,
            'uf'             => $validated['uf'] ?? null,
            'cep'            => $validated['cep'] ?? null,
        ]);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit(Cliente $cliente)
    {
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'razao_social'   => ['required','string','max:255'],
            'email'          => [
                'required','email','max:255',
                Rule::unique('clientes','email')->ignore($cliente->id),
            ],
            'cnpj'           => ['nullable','string','max:18','required_without:cpf'],
            'cpf'            => ['nullable','string','max:14','required_without:cnpj'],
            'inscr_estadual' => ['nullable','string','max:30'],

            'telefone'       => ['nullable','string','max:20'],

            'endereco'       => ['nullable','string','max:255'],
            'numero'         => ['nullable','string','max:20'],
            'complemento'    => ['nullable','string','max:100'],
            'bairro'         => ['nullable','string','max:100'],
            'cidade'         => ['nullable','string','max:100'],
            'uf'             => ['nullable','string','size:2'],
            'cep'            => ['nullable','string','max:9'],
        ], [
            'cnpj.required_without' => 'Informe o CNPJ ou o CPF.',
            'cpf.required_without'  => 'Informe o CPF ou o CNPJ.',
        ]);

        if (!empty($validated['uf'])) {
            $validated['uf'] = strtoupper($validated['uf']);
        }

        $cliente->update([
            'razao_social'   => $validated['razao_social'],
            'email'          => $validated['email'],
            'cnpj'           => $validated['cnpj'] ?? null,
            'cpf'            => $validated['cpf'] ?? null,
            'inscr_estadual' => $validated['inscr_estadual'] ?? null,
            'telefone'       => $validated['telefone'] ?? null,
            'endereco'       => $validated['endereco'] ?? null,
            'numero'         => $validated['numero'] ?? null,
            'complemento'    => $validated['complemento'] ?? null,
            'bairro'         => $validated['bairro'] ?? null,
            'cidade'         => $validated['cidade'] ?? null,
            'uf'             => $validated['uf'] ?? null,
            'cep'            => $validated['cep'] ?? null,
        ]);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente excluído com sucesso!');
    }
}
