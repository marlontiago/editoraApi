<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /** Helper para respostas de erro JSON */
    protected function jsonError(string $message, array $errors = [], int $status = 422)
    {
        return response()->json(['message' => $message, 'errors' => $errors ?: null], $status);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 200));

        $clientes = Cliente::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($w) use ($term) {
                    $w->where('razao_social', 'ILIKE', $term)
                      ->orWhere('email', 'ILIKE', $term)
                      ->orWhere('cnpj', 'ILIKE', $term)
                      ->orWhere('cpf', 'ILIKE', $term);
                });
            })
            ->orderBy('razao_social')
            ->paginate($perPage)
            ->appends($request->only('q','per_page'));

        if ($request->wantsJson()) {
            return response()->json($clientes);
        }

        return view('admin.clientes.index', compact('clientes'));
    }

    /** (API) detalhe; (web) você não usa show, então só JSON */
    public function show(Request $request, Cliente $cliente)
    {
        if ($request->wantsJson()) {
            return response()->json($cliente);
        }
        // opcional: redirecionar para edição no modo web
        return redirect()->route('admin.clientes.edit', $cliente);
    }

    public function create(Request $request)
    {
        if ($request->wantsJson()) {
            // poderia devolver listas auxiliares se existirem (ex.: UFs)
            return response()->json([
                'meta' => ['ufs' => ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO']]
            ]);
        }

        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => ['nullable','integer','exists:users,id'],
            'razao_social'   => ['required','string','max:255'],
            'email'          => ['required','email','max:255','unique:clientes,email'],
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

        $resolvedUserId = auth()->id() ?: ($validated['user_id'] ?? null);

        if (!$resolvedUserId && $request->wantsJson()) {
            return response()->json([
                'message' => 'Informe "user_id" ou autentique-se para criar clientes.',
                'errors'  => ['user_id' => ['Obrigatório quando não autenticado.']]
            ], 422);
        }

        $cliente = Cliente::create([
            'user_id'        => $resolvedUserId,
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

        if ($request->wantsJson()) {
            return response()->json($cliente, 201);
        }

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit(Request $request, Cliente $cliente)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'cliente' => $cliente,
                'meta'    => ['ufs' => ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO']]
            ]);
        }

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

        if ($request->wantsJson()) {
            return response()->json($cliente);
        }

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente excluído com sucesso!');
    }
}
