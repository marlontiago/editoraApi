<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteService
{
    public function paginateIndex(int $perPage = 10)
    {
        return Cliente::orderBy('razao_social')->paginate($perPage);
    }

    public function findOrFail(int $id): Cliente
    {
        return Cliente::findOrFail($id);
    }

    /**
     * Regras e mensagens exatamente como seu controller (store).
     */
    public function validateStore(Request $request): array
    {
        return $request->validate([
            'razao_social'   => ['required','string','max:255'],
            'email'          => ['nullable','email','max:255','unique:clientes,email'],

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
    }

    /**
     * Regras e mensagens exatamente como seu controller (update).
     * Observação: aqui seu email é required (igual você fez).
     */
    public function validateUpdate(Request $request, Cliente $cliente): array
    {
        return $request->validate([
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
    }

    /**
     * Normalizações (UF/UF2 e listas), igual seu controller.
     */
    private function normalizePayload(Request $request, array $validated): array
    {
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

        return [
            'razao_social'   => $validated['razao_social'],
            'email'          => $validated['email'] ?? null,

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
        ];
    }

    public function create(Request $request): Cliente
    {
        $validated = $this->validateStore($request);

        $data = $this->normalizePayload($request, $validated);

        // igual seu controller
        $data['user_id'] = auth()->id();

        return Cliente::create($data);
    }

    public function update(Request $request, Cliente $cliente): Cliente
    {
        $validated = $this->validateUpdate($request, $cliente);

        $data = $this->normalizePayload($request, $validated);

        $cliente->update($data);

        return $cliente->refresh();
    }

    public function delete(Cliente $cliente): void
    {
        $cliente->delete();
    }
}
