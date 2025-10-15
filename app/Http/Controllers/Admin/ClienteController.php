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

            // documentos
            'cnpj'           => ['nullable','string','max:18','required_without:cpf'],
            'cpf'            => ['nullable','string','max:14','required_without:cnpj'],
            'inscr_estadual' => ['nullable','string','max:30'],

            // contato legado + listas novas (se você já atualizou o form)
            'telefone'       => ['nullable','string','max:20'],
            'telefones'      => ['nullable','array'],
            'telefones.*'    => ['nullable','string','max:30'],
            'emails'         => ['nullable','array'],
            'emails.*'       => ['nullable','email','max:255'],

            // endereço principal
            'endereco'       => ['nullable','string','max:255'],
            'numero'         => ['nullable','string','max:20'],
            'complemento'    => ['nullable','string','max:100'],
            'bairro'         => ['nullable','string','max:100'],
            'cidade'         => ['nullable','string','max:100'],
            'uf'             => ['nullable','string','size:2'],
            'cep'            => ['nullable','string','max:9'],

            // endereço secundário
            'endereco2'      => ['nullable','string','max:255'],
            'numero2'        => ['nullable','string','max:20'],
            'complemento2'   => ['nullable','string','max:100'],
            'bairro2'        => ['nullable','string','max:100'],
            'cidade2'        => ['nullable','string','max:100'],
            'uf2'            => ['nullable','string','size:2'],
            'cep2'           => ['nullable','string','max:9'],
        ], [
            'cnpj.required_without' => 'Informe o CNPJ ou o CPF.',
            'cpf.required_without'  => 'Informe o CPF ou o CNPJ.',
        ]);

        if (!empty($validated['uf']))  $validated['uf']  = strtoupper($validated['uf']);
        if (!empty($validated['uf2'])) $validated['uf2'] = strtoupper($validated['uf2']);

        // normaliza listas
        $telefones = collect($request->input('telefones', []))
            ->map(fn($t) => trim((string)$t))
            ->filter(fn($t) => $t !== '')
            ->values()
            ->all();

        $emails = collect($request->input('emails', []))
            ->map(fn($e) => trim((string)$e))
            ->filter(fn($e) => $e !== '')
            ->values()
            ->all();

        Cliente::create([
            'user_id'        => auth()->id(),

            'razao_social'   => $validated['razao_social'],
            'email'          => $validated['email'],

            'cnpj'           => $validated['cnpj'] ?? null,
            'cpf'            => $validated['cpf'] ?? null,
            'inscr_estadual' => $validated['inscr_estadual'] ?? null,

            // legado único + novas listas
            'telefone'       => $validated['telefone'] ?? null,
            'telefones'      => !empty($telefones) ? $telefones : null,
            'emails'         => !empty($emails) ? $emails : null,

            // endereço principal
            'endereco'       => $validated['endereco'] ?? null,
            'numero'         => $validated['numero'] ?? null,
            'complemento'    => $validated['complemento'] ?? null,
            'bairro'         => $validated['bairro'] ?? null,
            'cidade'         => $validated['cidade'] ?? null,
            'uf'             => $validated['uf'] ?? null,
            'cep'            => $validated['cep'] ?? null,

            // endereço secundário
            'endereco2'      => $validated['endereco2'] ?? null,
            'numero2'        => $validated['numero2'] ?? null,
            'complemento2'   => $validated['complemento2'] ?? null,
            'bairro2'        => $validated['bairro2'] ?? null,
            'cidade2'        => $validated['cidade2'] ?? null,
            'uf2'            => $validated['uf2'] ?? null,
            'cep2'           => $validated['cep2'] ?? null,
        ]);

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
            'telefones'      => ['nullable','array'],
            'telefones.*'    => ['nullable','string','max:30'],
            'emails'         => ['nullable','array'],
            'emails.*'       => ['nullable','email','max:255'],

            'endereco'       => ['nullable','string','max:255'],
            'numero'         => ['nullable','string','max:20'],
            'complemento'    => ['nullable','string','max:100'],
            'bairro'         => ['nullable','string','max:100'],
            'cidade'         => ['nullable','string','max:100'],
            'uf'             => ['nullable','string','size:2'],
            'cep'            => ['nullable','string','max:9'],

            'endereco2'      => ['nullable','string','max:255'],
            'numero2'        => ['nullable','string','max:20'],
            'complemento2'   => ['nullable','string','max:100'],
            'bairro2'        => ['nullable','string','max:100'],
            'cidade2'        => ['nullable','string','max:100'],
            'uf2'            => ['nullable','string','size:2'],
            'cep2'           => ['nullable','string','max:9'],
        ], [
            'cnpj.required_without' => 'Informe o CNPJ ou o CPF.',
            'cpf.required_without'  => 'Informe o CPF ou o CNPJ.',
        ]);

        if (!empty($validated['uf']))  $validated['uf']  = strtoupper($validated['uf']);
        if (!empty($validated['uf2'])) $validated['uf2'] = strtoupper($validated['uf2']);

        $telefones = collect($request->input('telefones', []))
            ->map(fn($t) => trim((string)$t))
            ->filter(fn($t) => $t !== '')
            ->values()
            ->all();

        $emails = collect($request->input('emails', []))
            ->map(fn($e) => trim((string)$e))
            ->filter(fn($e) => $e !== '')
            ->values()
            ->all();

        $cliente->update([
            'razao_social'   => $validated['razao_social'],
            'email'          => $validated['email'],

            'cnpj'           => $validated['cnpj'] ?? null,
            'cpf'            => $validated['cpf'] ?? null,
            'inscr_estadual' => $validated['inscr_estadual'] ?? null,

            'telefone'       => $validated['telefone'] ?? null,
            'telefones'      => !empty($telefones) ? $telefones : null,
            'emails'         => !empty($emails) ? $emails : null,

            'endereco'       => $validated['endereco'] ?? null,
            'numero'         => $validated['numero'] ?? null,
            'complemento'    => $validated['complemento'] ?? null,
            'bairro'         => $validated['bairro'] ?? null,
            'cidade'         => $validated['cidade'] ?? null,
            'uf'             => $validated['uf'] ?? null,
            'cep'            => $validated['cep'] ?? null,

            'endereco2'      => $validated['endereco2'] ?? null,
            'numero2'        => $validated['numero2'] ?? null,
            'complemento2'   => $validated['complemento2'] ?? null,
            'bairro2'        => $validated['bairro2'] ?? null,
            'cidade2'        => $validated['cidade2'] ?? null,
            'uf2'            => $validated['uf2'] ?? null,
            'cep2'           => $validated['cep2'] ?? null,
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
