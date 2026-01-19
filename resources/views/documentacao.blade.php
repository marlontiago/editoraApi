{{-- resources/views/documentacao/index.blade.php --}}
<x-app-layout>

    {{-- Cabeçalho do Breeze --}}
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <span class="text-lg font-semibold text-gray-900">
                Documentação da API
            </span>
        </div>
    </x-slot>

    {{-- Conteúdo --}}
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
             x-data="docsTabs()">

            {{-- Introdução --}}
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Documentação da API
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Endpoints, autenticação, exemplos de request/response e regras de negócio.
                </p>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex flex-wrap gap-2">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button
                            type="button"
                            @click="setActive(tab.key)"
                            class="px-4 py-2 text-sm rounded-t-md border"
                            :class="active === tab.key
                                ? 'bg-white border-gray-300 border-b-white text-gray-900'
                                : 'bg-gray-50 border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        >
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </nav>
            </div>

            {{-- ========================= --}}
            {{-- ABA: VISÃO GERAL --}}
            {{-- ========================= --}}
            <section x-show="active === 'overview'" x-cloak>
                <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-4">

                    <h2 class="text-lg font-semibold text-gray-900">
                        Visão Geral
                    </h2>

                    <div class="text-sm text-gray-700 space-y-2">
                        <p>
                            A API utiliza <strong>Laravel Sanctum</strong> para autenticação via
                            <strong>Bearer Token</strong>.
                        </p>
                    </div>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json</code></pre>

                    <ul class="list-disc pl-6 text-sm text-gray-700">
                        <li>Rotas REST com <code>Route::apiResource</code></li>
                        <li>Respostas padronizadas em JSON</li>
                        <li>Validações retornam HTTP 422</li>
                    </ul>

                </div>
            </section>

            {{-- ========================= --}}
{{-- ABA: AUTENTICAÇÃO (SANCTUM) --}}
{{-- ========================= --}}
<section x-show="active === 'auth'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Autenticação da API (Laravel Sanctum)
            </h2>
            <p class="text-sm text-gray-500">
                A API utiliza Bearer Token (Sanctum). Após login, envie o token no header <code>Authorization</code>.
            </p>
        </div>

        {{-- Como autenticar --}}
        <div class="text-sm text-gray-700 space-y-2">
            <h3 class="font-semibold text-gray-900">Header obrigatório nas rotas protegidas</h3>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-xs overflow-auto">
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
            </div>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Protegido</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/auth/login</td>
                        <td class="px-4 py-2">Não</td>
                        <td class="px-4 py-2">Realiza login e retorna token</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/auth/me</td>
                        <td class="px-4 py-2">Sim</td>
                        <td class="px-4 py-2">Retorna usuário autenticado e roles</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/auth/logout</td>
                        <td class="px-4 py-2">Sim</td>
                        <td class="px-4 py-2">Revoga o token atual (logout)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- LOGIN --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Login</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">POST /api/auth/login</span>
            </p>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Validação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">email</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">email válido</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">password</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">string</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-gray-700">Exemplo de requisição:</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "email": "admin@example.com",
  "password": "admin123"
}</code></pre>

            <p class="text-sm text-gray-700">Resposta de sucesso (200):</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com"
  }
}</code></pre>

            <p class="text-sm text-gray-700">Resposta de erro (401):</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": false,
  "message": "Credenciais inválidas."
}</code></pre>
        </div>

        {{-- ME --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Usuário autenticado</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">GET /api/auth/me</span>
            </p>

            <p class="text-sm text-gray-700">
                Requer o header <code>Authorization: Bearer</code>.
            </p>

            <p class="text-sm text-gray-700">Resposta (200):</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "...": "..."
  },
  "roles": ["admin"]
}</code></pre>
        </div>

        {{-- LOGOUT --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Logout</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">POST /api/auth/logout</span>
            </p>

            <p class="text-sm text-gray-700">
                Remove (revoga) o token atual do usuário autenticado.
            </p>

            <p class="text-sm text-gray-700">Resposta (200):</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true
}</code></pre>
        </div>

        {{-- Observações --}}
        <div class="text-sm text-gray-700 space-y-2">
            <h3 class="font-semibold text-gray-900">Observações</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>O token retornado deve ser armazenado pelo cliente e enviado em todas as rotas protegidas.</li>
                <li>O endpoint <code>/auth/logout</code> revoga apenas o token atual (<code>currentAccessToken()</code>).</li>
                <li>O endpoint <code>/auth/me</code> inclui <code>roles</code> quando o projeto usa Spatie Permissions (<code>getRoleNames()</code>).</li>
            </ul>
        </div>

    </div>
</section>


            {{-- ========================= --}}
{{-- ABA: CLIENTES --}}
{{-- ========================= --}}
<section x-show="active === 'clientes'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Clientes (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                CRUD de clientes. Valida CNPJ/CPF (um dos dois é obrigatório) e suporta listas de
                telefones/emails.
            </p>
        </div>

        {{-- Base --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border border-gray-200 rounded px-2 py-1 text-xs">
                /api/admin/clientes
            </code>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/clientes</td>
                        <td class="px-4 py-2">Listar clientes (paginado)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/clientes</td>
                        <td class="px-4 py-2">Criar cliente</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/clientes/{cliente}</td>
                        <td class="px-4 py-2">Detalhar cliente</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/clientes/{cliente}</td>
                        <td class="px-4 py-2">
                            Atualizar cliente
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/clientes/{cliente}</td>
                        <td class="px-4 py-2">Excluir cliente</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="rounded-md bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900">
            <strong>Observação sobre UPDATE (PUT):</strong>
            <p class="mt-1">
                Para requisições enviadas como <code>multipart/form-data</code>, o endpoint de atualização
                deve ser chamado usando o método <strong>POST</strong>, informando o campo
                <code>_method=PUT</code> no body da requisição.
            </p>
        </div>

        {{-- Regras de negócio --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras de negócio</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>CNPJ ou CPF</strong>: deve informar pelo menos um (<code>required_without</code>).</li>
                <li><strong>Store:</strong> <code>email</code> é opcional e único em <code>clientes.email</code>.</li>
                <li><strong>Update:</strong> <code>email</code> é <strong>obrigatório</strong> e único (ignora o próprio cliente).</li>
                <li><strong>UF/UF2</strong> são normalizadas para maiúsculo (ex: <code>sp</code> → <code>SP</code>).</li>
                <li><strong>telefones/emails</strong> aceitam arrays; o service remove itens vazios e salva como <code>null</code> se vier vazio.</li>
                <li>No create, <code>user_id</code> do cliente é definido como <code>auth()->id()</code>.</li>
            </ul>
        </div>

        {{-- Campos --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Campos do Cliente</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr><td class="px-4 py-2 font-mono">razao_social</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Sim</td><td class="px-4 py-2">max 255</td></tr>

                        <tr><td class="px-4 py-2 font-mono">email</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Store: Não / Update: Sim</td><td class="px-4 py-2">email válido, max 255, único</td></tr>

                        <tr><td class="px-4 py-2 font-mono">cnpj</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Condicional</td><td class="px-4 py-2">max 18; obrigatório se <code>cpf</code> não vier</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cpf</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Condicional</td><td class="px-4 py-2">max 14; obrigatório se <code>cnpj</code> não vier</td></tr>

                        <tr><td class="px-4 py-2 font-mono">inscr_estadual</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 30</td></tr>

                        <tr><td class="px-4 py-2 font-mono">telefone</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>

                        <tr><td class="px-4 py-2 font-mono">telefones</td><td class="px-4 py-2">array</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">lista de strings (max 30 cada); itens vazios são removidos</td></tr>

                        <tr><td class="px-4 py-2 font-mono">emails</td><td class="px-4 py-2">array</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">lista de emails válidos; itens vazios são removidos</td></tr>

                        <tr><td class="px-4 py-2 font-mono">endereco</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">numero</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
                        <tr><td class="px-4 py-2 font-mono">complemento</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">bairro</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cidade</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">uf</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cep</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>

                        <tr><td class="px-4 py-2 font-mono">endereco2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">numero2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
                        <tr><td class="px-4 py-2 font-mono">complemento2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">bairro2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cidade2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">uf2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cep2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700">
                <strong>Mensagens:</strong>
                <ul class="list-disc pl-6 mt-1">
                    <li><code>cnpj.required_without</code> → "Informe o CNPJ ou o CPF."</li>
                    <li><code>cpf.required_without</code> → "Informe o CPF ou o CNPJ."</li>
                </ul>
            </div>
        </div>

        {{-- Exemplo — Store --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar Cliente (POST /api/clientes)
            </h3>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "razao_social": "Cliente Exemplo LTDA",
  "email": "financeiro@cliente.com",

  "cnpj": "12.345.678/0001-90",
  "cpf": null,
  "inscr_estadual": "123.456.789.000",

  "telefone": "(11) 99999-9999",
  "telefones": ["(11) 98888-7777", "(11) 97777-6666"],
  "emails": ["financeiro@cliente.com", "compras@cliente.com"],

  "endereco": "Rua Exemplo",
  "numero": "100",
  "complemento": "Sala 301",
  "bairro": "Centro",
  "cidade": "São Paulo",
  "uf": "SP",
  "cep": "01000-000",

  "endereco2": "Av. Secundária",
  "numero2": "200",
  "complemento2": "Bloco B",
  "bairro2": "Bairro 2",
  "cidade2": "Campinas",
  "uf2": "SP",
  "cep2": "13000-000"
}</code></pre>
        </div>

        {{-- Exemplo — Update --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Atualizar Cliente (PUT /api/clientes/{cliente})
            </h3>

            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                No <strong>update</strong> seu service exige <code>email</code> como <strong>obrigatório</strong>.
            </div>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "razao_social": "Cliente Exemplo LTDA (Alterado)",
  "email": "financeiro@cliente.com",

  "cpf": "123.456.789-00",
  "cnpj": null,

  "telefone": "(11) 90000-0000",
  "telefones": ["(11) 90000-0000"],
  "emails": ["financeiro@cliente.com"],

  "cidade": "São Paulo",
  "uf": "SP"
}</code></pre>
        </div>

    </div>
</section>


            

            {{-- ========================= --}}
            {{-- ABA: DIRETOR COMERCIAL --}}
            {{-- ========================= --}}
            <section x-show="active === 'diretor-comercials'" x-cloak>
                <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-6">

                    {{-- Cabeçalho --}}
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Diretor Comercial (Admin)
                        </h2>
                        <p class="text-sm text-gray-500">
                            CRUD de diretores comerciais com criação automática de usuário.
                        </p>
                    </div>

                    {{-- Base da rota --}}
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-gray-500">Base:</span>
                        <code class="bg-gray-100 border border-gray-200 rounded px-2 py-1 text-xs">
                            /api/diretor-comercials
                        </code>
                    </div>

                    {{-- Tabela de endpoints --}}
                    <div class="overflow-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-2 text-left">Método</th>
                                    <th class="px-4 py-2 text-left">Endpoint</th>
                                    <th class="px-4 py-2 text-left">Descrição</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono">GET</td>
                                    <td class="px-4 py-2 font-mono">/api/diretor-comercials</td>
                                    <td class="px-4 py-2">Listar diretores (paginado)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">POST</td>
                                    <td class="px-4 py-2 font-mono">/api/diretor-comercials</td>
                                    <td class="px-4 py-2">Criar diretor comercial</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">GET</td>
                                    <td class="px-4 py-2 font-mono">/api/diretor-comercials/{diretor_comercial}</td>
                                    <td class="px-4 py-2">Detalhar diretor comercial</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">PUT</td>
                                    <td class="px-4 py-2 font-mono">/api/diretor-comercials/{diretor_comercial}</td>
                                    <td class="px-4 py-2">Atualizar diretor comercial</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">DELETE</td>
                                    <td class="px-4 py-2 font-mono">/api/diretor-comercials/{diretor_comercial}</td>
                                    <td class="px-4 py-2">Excluir diretor comercial</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Regras de negócio --}}
                    <div class="space-y-2 text-sm text-gray-700">
                        <h3 class="font-semibold text-gray-900">Regras de negócio</h3>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Cria automaticamente um <strong>usuário</strong> para o diretor.</li>
                            <li>Senha do usuário é gerada automaticamente (não retornada).</li>
                            <li>Atualização sincroniza <code>nome</code> e <code>email</code> do usuário (se existir).</li>
                            <li>Exclusão remove apenas o Diretor Comercial (usuário permanece).</li>
                        </ul>
                    </div>

                    {{-- Exemplo (completo) --}}
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">
                            Exemplo — Criar Diretor Comercial
                        </h3>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "nome": "Diretor A",
  "email": "diretor@empresa.com",
  "telefone": "(11) 99999-9999",
  "percentual_vendas": 5,
  "logradouro": "Rua Exemplo",
  "numero": "100",
  "complemento": "Sala 301",
  "bairro": "Centro",
  "cidade": "São Paulo",
  "estado": "SP",
  "cep": "01000-000"
}</code></pre>
                    </div>

                </div>
            </section>

            {{-- ========================= --}}
            {{-- ABAS PLACEHOLDER --}}
            {{-- ========================= --}}
          <section x-show="active === 'gestores'" x-cloak>
  <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

    {{-- Cabeçalho --}}
    <div>
      <h2 class="text-lg font-semibold text-gray-900">
        Gestores (Admin)
      </h2>
      <p class="text-sm text-gray-500">
        Cadastro completo de gestores com usuário automático, UFs (gestor_ufs), contratos/anexos
        e vínculo com distribuidores.
      </p>
    </div>

    {{-- Base --}}
    <div class="text-sm">
      <span class="text-gray-500">Base:</span>
      <code class="bg-gray-100 border rounded px-2 py-1 text-xs">
        /api/admin/gestores
      </code>
    </div>

    {{-- Rotas (apiResource) --}}
    <div class="overflow-auto border border-gray-200 rounded-lg">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">Método</th>
            <th class="px-4 py-2 text-left">Endpoint</th>
            <th class="px-4 py-2 text-left">Descrição</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr><td class="px-4 py-2 font-mono">GET</td><td class="px-4 py-2 font-mono">/api/admin/gestores</td><td>Listar gestores (paginado)</td></tr>
          <tr><td class="px-4 py-2 font-mono">POST</td><td class="px-4 py-2 font-mono">/api/admin/gestores</td><td>Criar gestor</td></tr>
          <tr><td class="px-4 py-2 font-mono">GET</td><td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}</td><td>Detalhar gestor</td></tr>
          <tr><td class="px-4 py-2 font-mono">PUT</td><td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}</td><td>Atualizar gestor</td></tr>
          <tr><td class="px-4 py-2 font-mono">DELETE</td><td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}</td><td>Excluir gestor</td></tr>
        </tbody>
      </table>
    </div>

    {{-- Rotas extras --}}
    <div class="overflow-auto border border-gray-200 rounded-lg">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">Método</th>
            <th class="px-4 py-2 text-left">Endpoint</th>
            <th class="px-4 py-2 text-left">Descrição</th>
          </tr>
        </thead>
        <tbody class="divide-y">

          {{-- ANEXOS / CONTRATOS DO GESTOR --}}
          <tr>
            <td class="px-4 py-2 font-mono">DELETE</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}/anexos/{anexo}</td>
            <td>Excluir anexo (remove arquivo do storage e registro)</td>
          </tr>

          <tr>
            <td class="px-4 py-2 font-mono">POST</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}/anexos/{anexo}/ativar</td>
            <td>Marcar anexo como ativo (aplica percentual/vencimento do ativo)</td>
          </tr>

          <tr>
            <td class="px-4 py-2 font-mono">GET</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}/anexos/{anexo}</td>
            <td>Detalhar anexo (controller separado)</td>
          </tr>

          <tr>
            <td class="px-4 py-2 font-mono">PUT</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}/anexos/{anexo}</td>
            <td>Atualizar anexo (controller separado)</td>
          </tr>

          {{-- VÍNCULO DISTRIBUIDORES (se existir no seu api.php) --}}
          <tr>
            <td class="px-4 py-2 font-mono">POST</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/vincular</td>
            <td>Vincular distribuidores a gestores (batch)</td>
          </tr>

          {{-- UFS (se existir no seu api.php) --}}
          <tr>
            <td class="px-4 py-2 font-mono">GET</td>
            <td class="px-4 py-2 font-mono">/api/admin/gestores/{gestor}/ufs</td>
            <td>Listar UFs vinculadas ao gestor</td>
          </tr>

        </tbody>
      </table>
    </div>

    {{-- Atenção multipart --}}
    <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
      <strong>Atenção:</strong>
      Se você enviar <code>contratos[*].arquivo</code> (PDF),
      o request deve ser <code>multipart/form-data</code>. Sem upload, pode usar <code>application/json</code>.
    </div>

    {{-- Regras importantes --}}
    <div class="space-y-2 text-sm text-gray-700">
      <h3 class="font-semibold text-gray-900">Regras de negócio</h3>
      <ul class="list-disc pl-6 space-y-1">
        <li>Ao criar, se <code>email</code> estiver vazio, o sistema cria um e-mail placeholder automaticamente.</li>
        <li>Se <code>password</code> estiver vazio, uma senha aleatória é gerada automaticamente.</li>
        <li>O gestor possui UFs vinculadas (ex.: <code>gestor_ufs</code>) via <code>estados_uf</code>.</li>
        <li>Apenas um anexo pode ficar com <code>ativo=true</code>; o ativo aplica automaticamente <code>percentual_vendas</code> e <code>vencimento_contrato</code> no gestor.</li>
        <li>Ao ativar um anexo, os demais anexos ativos do mesmo gestor são desativados.</li>

        {{-- NOVO: contrato por cidade --}}
        <li>
          Existe o tipo <code>contrato_cidade</code> em <code>contratos[*].tipo</code>. Nesse caso, é obrigatório enviar
          <code>contratos[*].cidade_id</code>, e o percentual passa a valer apenas para a cidade escolhida (prioridade nos cálculos).
          As cidades disponíveis devem pertencer às UFs do gestor.
        </li>
        <li>
          Para <code>contrato_cidade</code>, o campo <code>contratos[*].ativo</code> não é utilizado (o percentual é aplicado automaticamente para a cidade informada).
        </li>
      </ul>
    </div>

    {{-- Campos --}}
    <div class="space-y-2 text-sm text-gray-700">
      <h3 class="font-semibold text-gray-900">Campos do Gestor</h3>

      <div class="overflow-auto border border-gray-200 rounded-lg">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left">Campo</th>
              <th class="px-4 py-2 text-left">Tipo</th>
              <th class="px-4 py-2 text-left">Obrigatório</th>
              <th class="px-4 py-2 text-left">Regras / Observações</th>
            </tr>
          </thead>

          <tbody class="divide-y">
            {{-- DADOS PRINCIPAIS --}}
            <tr>
              <td class="px-4 py-2 font-mono">razao_social</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Sim</td>
              <td class="px-4 py-2">max 255</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">cnpj</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Geralmente Sim</td>
              <td class="px-4 py-2">max 18; validar formato; pode ser único (se você aplicou unique)</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">representante_legal</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">max 255</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">cpf</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">max 14</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">rg</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">max 30</td>
            </tr>

            {{-- LOGIN / USUÁRIO --}}
            <tr>
              <td class="px-4 py-2 font-mono">email</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">
                email válido, max 255, único em users.email;
                se vazio, o sistema cria um placeholder automaticamente
              </td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">password</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">se vazio, o sistema gera uma senha aleatória automaticamente</td>
            </tr>

            {{-- CONTATO BÁSICO --}}
            <tr>
              <td class="px-4 py-2 font-mono">telefone</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">max 20</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">telefones</td>
              <td class="px-4 py-2">array</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">lista de strings; itens vazios removidos; salva null se vier vazio</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">emails</td>
              <td class="px-4 py-2">array</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">lista de emails válidos; itens vazios removidos; salva null se vier vazio</td>
            </tr>

            {{-- ENDEREÇO 1 --}}
            <tr><td class="px-4 py-2 font-mono">endereco</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
            <tr><td class="px-4 py-2 font-mono">numero</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
            <tr><td class="px-4 py-2 font-mono">complemento</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">bairro</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">cidade</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">uf</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
            <tr><td class="px-4 py-2 font-mono">cep</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>

            {{-- ENDEREÇO 2 --}}
            <tr><td class="px-4 py-2 font-mono">endereco2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
            <tr><td class="px-4 py-2 font-mono">numero2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
            <tr><td class="px-4 py-2 font-mono">complemento2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">bairro2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">cidade2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
            <tr><td class="px-4 py-2 font-mono">uf2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
            <tr><td class="px-4 py-2 font-mono">cep2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>

            {{-- COMISSÃO / CONTRATO --}}
            <tr>
              <td class="px-4 py-2 font-mono">percentual_vendas</td>
              <td class="px-4 py-2">number</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">ex: 5; pode ser sobrescrito ao ativar anexo/contrato</td>
            </tr>

            {{-- UFS --}}
            <tr>
              <td class="px-4 py-2 font-mono">estados_uf</td>
              <td class="px-4 py-2">array</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">lista de UFs (ex: ["SP","RJ"]); normaliza maiúsculo; salva em gestor_ufs</td>
            </tr>

            {{-- CONTRATOS/ANEXOS --}}
            <tr>
              <td class="px-4 py-2 font-mono">contratos</td>
              <td class="px-4 py-2">array</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">lista de objetos; ao ativar um, desativa os demais</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].tipo</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Depende</td>
              <td class="px-4 py-2">
                valores: <code>contrato</code>, <code>aditivo</code>, <code>outro</code>, <code>contrato_cidade</code>.
                Se <code>contrato_cidade</code>, exigir <code>contratos[*].cidade_id</code>.
              </td>
            </tr>

            {{-- NOVO: cidade_id para contrato por cidade --}}
            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].cidade_id</td>
              <td class="px-4 py-2">int</td>
              <td class="px-4 py-2">Somente se tipo = <code>contrato_cidade</code></td>
              <td class="px-4 py-2">
                ID da cidade para aplicar o percentual. Deve ser uma cidade pertencente às UFs do gestor.
              </td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].descricao</td>
              <td class="px-4 py-2">string</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">texto livre</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].assinado</td>
              <td class="px-4 py-2">boolean</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">1/0 no form-data</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].percentual_vendas</td>
              <td class="px-4 py-2">number</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">
                se <code>ativo=true</code> (para tipos comuns), aplica no gestor automaticamente.
                Para <code>contrato_cidade</code>, aplica na cidade informada (prioridade nos cálculos).
              </td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].ativo</td>
              <td class="px-4 py-2">boolean</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">
                apenas 1 ativo=true por gestor (para tipos comuns).
                Para <code>contrato_cidade</code>, não usar este campo.
              </td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].data_assinatura</td>
              <td class="px-4 py-2">date (Y-m-d)</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">ex: 2025-01-01</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].validade_meses</td>
              <td class="px-4 py-2">int</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">ex: 12, 24</td>
            </tr>

            <tr>
              <td class="px-4 py-2 font-mono">contratos[*].arquivo</td>
              <td class="px-4 py-2">file (PDF)</td>
              <td class="px-4 py-2">Não</td>
              <td class="px-4 py-2">requer multipart/form-data</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700">
        <strong>Observações:</strong>
        <ul class="list-disc pl-6 mt-1 space-y-1">
          <li>Campos em <code>telefones</code> / <code>emails</code>: o service remove itens vazios e salva <code>null</code> se a lista ficar vazia.</li>
          <li><code>uf</code>, <code>uf2</code> e <code>estados_uf[*]</code> são normalizados para maiúsculo.</li>
          <li>Em <code>contratos</code>: apenas 1 item pode ficar com <code>ativo</code> (exceto <code>contrato_cidade</code>).</li>
          <li>Em <code>contrato_cidade</code>: <code>cidade_id</code> é obrigatório e o percentual tem prioridade para a cidade.</li>
        </ul>
      </div>
    </div>

    {{-- CREATE (payload completo) --}}
    <div>
      <h3 class="font-semibold text-gray-900 mb-2">
        Exemplo — Criar Gestor (payload completo)
      </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "razao_social": "Editora Alfa LTDA",
  "cnpj": "12.345.678/0001-90",
  "representante_legal": "João da Silva",
  "cpf": "123.456.789-00",
  "rg": "12.345.678-9",

  "email": "gestor@empresa.com",
  "password": "12345678",

  "telefone": "(11) 3333-4444",
  "telefones": ["1133334444", "1199998888"],
  "emails": ["financeiro@empresa.com", "comercial@empresa.com"],

  "endereco": "Rua Principal",
  "numero": "100",
  "complemento": "Sala 301",
  "bairro": "Centro",
  "cidade": "São Paulo",
  "uf": "SP",
  "cep": "01000-000",

  "endereco2": "Rua Secundária",
  "numero2": "200",
  "bairro2": "Jardins",
  "cidade2": "São Paulo",
  "uf2": "SP",
  "cep2": "01400-000",

  "percentual_vendas": 5,

  "estados_uf": ["SP", "RJ", "MG"],

  "contratos": [
    {
      "tipo": "contrato",
      "descricao": "Contrato principal",
      "assinado": true,
      "percentual_vendas": 6,
      "ativo": true,
      "data_assinatura": "2024-01-01",
      "validade_meses": 24
    },
    {
      "tipo": "contrato_cidade",
      "cidade_id": 123,
      "descricao": "Percentual específico para a cidade",
      "assinado": true,
      "percentual_vendas": 9,
      "data_assinatura": "2025-01-01",
      "validade_meses": 12
    }
  ]
}</code></pre>

      <p class="text-sm text-gray-600 mt-2">
        Se enviar <code>contratos[0][arquivo]</code> (ou qualquer índice), troque o request para <code>multipart/form-data</code> e envie o PDF no campo.
      </p>
    </div>

    {{-- UPDATE (payload completo) --}}
    <div>
      <h3 class="font-semibold text-gray-900 mb-2">
        Exemplo — Atualizar Gestor (payload completo)
      </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "razao_social": "Editora Alfa (Atualizada)",
  "email": "novoemail@empresa.com",
  "percentual_vendas": 7,
  "estados_uf": ["SP", "RJ"],

  "contratos": [
    {
      "tipo": "aditivo",
      "descricao": "Aditivo 2025",
      "assinado": true,
      "percentual_vendas": 8,
      "ativo": false,
      "data_assinatura": "2025-01-01",
      "validade_meses": 12
    },
    {
      "tipo": "contrato_cidade",
      "cidade_id": 456,
      "descricao": "Aditivo por cidade (prioridade)",
      "assinado": true,
      "percentual_vendas": 10,
      "data_assinatura": "2025-02-01",
      "validade_meses": 6
    }
  ]
}</code></pre>
    </div>

    {{-- Exemplo multipart/form-data (contratos[*].arquivo) --}}
    <div>
      <h3 class="font-semibold text-gray-900 mb-2">
        Exemplo — Envio de contrato com arquivo (multipart/form-data)
      </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>razao_social: Editora Alfa LTDA
email: gestor@empresa.com
password: 12345678
percentual_vendas: 5
estados_uf[0]: SP
estados_uf[1]: RJ

contratos[0][tipo]: contrato
contratos[0][descricao]: Contrato principal
contratos[0][assinado]: 1
contratos[0][percentual_vendas]: 6
contratos[0][ativo]: 1
contratos[0][data_assinatura]: 2024-01-01
contratos[0][validade_meses]: 24
contratos[0][arquivo]: (PDF)

contratos[1][tipo]: contrato_cidade
contratos[1][cidade_id]: 123
contratos[1][descricao]: Contrato por cidade
contratos[1][assinado]: 1
contratos[1][percentual_vendas]: 9
contratos[1][data_assinatura]: 2025-01-01
contratos[1][validade_meses]: 12
contratos[1][arquivo]: (PDF opcional)</code></pre>
    </div>

    {{-- VÍNCULO DISTRIBUIDORES --}}
    <div>
      <h3 class="font-semibold text-gray-900 mb-2">
        Vincular Distribuidores
      </h3>

      <code class="text-xs">POST /api/admin/gestores/vincular</code>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "12": 5,
  "15": 5,
  "18": null
}</code></pre>

      <p class="text-sm text-gray-600 mt-2">
        Chave = ID do distribuidor • Valor = ID do gestor ou <code>null</code>
      </p>
    </div>

    {{-- UFS --}}
    <div>
      <h3 class="font-semibold text-gray-900 mb-2">
        UFs do Gestor
      </h3>

      <code class="text-xs">GET /api/admin/gestores/{gestor}/ufs</code>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>[
  "SP",
  "RJ",
  "MG"
]</code></pre>
    </div>

  </div>
</section>



            <section x-show="active === 'distribuidores'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Distribuidores (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                CRUD de distribuidores com usuário automático, vínculo obrigatório com gestor, cidades (pivot),
                contratos/anexos (inclui contrato por cidade) e endpoints auxiliares de filtro.
            </p>
        </div>

        {{-- Base --}}
        <div class="text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border rounded px-2 py-1 text-xs">
                /api/admin/distribuidores
            </code>
        </div>

        {{-- Rotas (apiResource) --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores</td>
                        <td>Listar distribuidores (paginado)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores</td>
                        <td>Criar distribuidor</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}</td>
                        <td>Detalhar distribuidor</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}</td>
                        <td>Atualizar distribuidor</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}</td>
                        <td>Excluir distribuidor</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Rotas extras --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y">

                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/por-gestor/{gestor}</td>
                        <td>Lista distribuidores por gestor (retorna <code>id</code> e <code>razao_social</code>)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/cidades/por-distribuidor/{distribuidor}</td>
                        <td>Lista cidades vinculadas ao distribuidor</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/cidades-por-ufs?ufs=SP,RJ</td>
                        <td>Lista cidades filtrando por UFs (query <code>ufs</code> separado por vírgula)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/cidades-por-gestor?gestor_id=5</td>
                        <td>Lista cidades filtrando pelas UFs do gestor</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}/anexos/{anexo}</td>
                        <td>Excluir anexo (remove arquivo do storage e registro)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}/anexos/{anexo}/ativar</td>
                        <td>Marcar anexo como ativo (aplica percentual/vencimento do ativo)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}/anexos/{anexo}</td>
                        <td>Detalhar anexo (controller separado)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/distribuidores/{distribuidor}/anexos/{anexo}</td>
                        <td>Atualizar anexo (controller separado)</td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- Atenção multipart --}}
        <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
            <strong>Atenção:</strong>
            Se você enviar <code>contratos[*][arquivo]</code> (PDF), o request deve ser
            <code>multipart/form-data</code>. Sem upload, pode usar <code>application/json</code>.
        </div>

        {{-- Regras importantes --}}
        {{-- Regras importantes --}}
<div class="space-y-2 text-sm text-gray-700">
  <h3 class="font-semibold text-gray-900">Regras de negócio</h3>

  <ul class="list-disc pl-6 space-y-1">
    <li>
      Ao criar, se <code>email</code> estiver vazio, o sistema cria um e-mail placeholder automaticamente.
    </li>
    <li>
      Se <code>password</code> estiver vazio, uma senha aleatória é gerada automaticamente.
    </li>
    <li>
      O distribuidor deve estar vinculado a um gestor, e suas cidades de atuação devem pertencer às UFs desse gestor.
    </li>
    <li>
      Uma mesma cidade não pode estar vinculada a mais de um distribuidor.
    </li>
    <li>
      Apenas um anexo pode ficar com <code>ativo=true</code>; o ativo aplica automaticamente
      <code>percentual_vendas</code> e <code>vencimento_contrato</code> no distribuidor.
    </li>
    <li>
      Ao ativar um anexo, os demais anexos ativos do mesmo distribuidor são desativados.
    </li>
    <li>
      Existe o tipo <code>contrato_cidade</code> em <code>contratos[*].tipo</code>. Nesse caso, é obrigatório enviar
      <code>contratos[*].cidade_id</code>, e o percentual passa a valer apenas para a cidade escolhida
      (prioridade nos cálculos). As cidades disponíveis devem pertencer às UFs do gestor.
    </li>
  </ul>
</div>



        {{-- Campos --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Campos do Distribuidor</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        {{-- VÍNCULO --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">gestor_id</td>
                            <td class="px-4 py-2">int</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">obrigatório no create e update; deve existir em <code>gestores.id</code></td>
                        </tr>

                        {{-- LOGIN / USUÁRIO --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">email</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">
                                email válido, max 255, único em <code>users.email</code>;
                                se vazio, o sistema cria um placeholder automaticamente
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">password</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">se vazio, o sistema gera uma senha aleatória automaticamente</td>
                        </tr>

                        {{-- DADOS PRINCIPAIS --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">razao_social</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">max 255</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cnpj</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Geralmente Sim</td>
                            <td class="px-4 py-2">max 18; validar formato; pode ser único (se você aplicou unique)</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">representante_legal</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">max 255</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cpf</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">max 14</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">rg</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">max 30</td>
                        </tr>

                        {{-- CONTATOS (LISTAS) --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">telefones</td>
                            <td class="px-4 py-2">array</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">lista de strings; itens vazios removidos; salva null se vier vazio</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">emails</td>
                            <td class="px-4 py-2">array</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">lista de emails válidos; itens vazios removidos; salva null se vier vazio</td>
                        </tr>

                        {{-- ENDEREÇO 1 --}}
                        <tr><td class="px-4 py-2 font-mono">endereco</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">numero</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
                        <tr><td class="px-4 py-2 font-mono">complemento</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">bairro</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cidade</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">uf</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cep</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>

                        {{-- ENDEREÇO 2 --}}
                        <tr><td class="px-4 py-2 font-mono">endereco2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">numero2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 20</td></tr>
                        <tr><td class="px-4 py-2 font-mono">complemento2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">bairro2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cidade2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 100</td></tr>
                        <tr><td class="px-4 py-2 font-mono">uf2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">size 2; normalizado para maiúsculo</td></tr>
                        <tr><td class="px-4 py-2 font-mono">cep2</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 9</td></tr>

                        {{-- CIDADES / FILTRO --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">uf_cidades</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">auxiliar para UI/filtro; ex: "SP"</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cities</td>
                            <td class="px-4 py-2">array[int]</td>
                            <td class="px-4 py-2">Não (depende)</td>
                            <td class="px-4 py-2">
                                lista de IDs de cidades; devem estar nas UFs do gestor; uma cidade não pode estar ocupada por outro distribuidor
                            </td>
                        </tr>

                        {{-- COMISSÃO / CONTRATO --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">percentual_vendas</td>
                            <td class="px-4 py-2">number</td>
                            <td class="px-4 py-2">Update: Sim</td>
                            <td class="px-4 py-2">no update é required (regra atual do service); pode ser aplicado via anexo ativo</td>
                        </tr>

                        {{-- CONTRATOS/ANEXOS --}}
                        <tr>
                            <td class="px-4 py-2 font-mono">contratos</td>
                            <td class="px-4 py-2">array</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">lista de objetos; ao ativar um, desativa os demais</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].tipo</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Depende</td>
                            <td class="px-4 py-2">
                                valores aceitos: <code>contrato</code>, <code>aditivo</code>, <code>outro</code>, <code>contrato_cidade</code><br>
                                obrigatório quando <code>contratos[*].arquivo</code> é enviado (required_with)
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].cidade_id</td>
                            <td class="px-4 py-2">int</td>
                            <td class="px-4 py-2">Somente se tipo=contrato_cidade</td>
                            <td class="px-4 py-2">
                                obrigatório quando <code>contratos[*].tipo = contrato_cidade</code>;
                                deve existir em <code>cities.id</code>
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].descricao</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">texto livre</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].assinado</td>
                            <td class="px-4 py-2">boolean</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">1/0 no form-data</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].percentual_vendas</td>
                            <td class="px-4 py-2">number</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">se ativo=true, aplica no distribuidor automaticamente</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].ativo</td>
                            <td class="px-4 py-2">boolean</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">apenas 1 ativo=true por distribuidor</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].data_assinatura</td>
                            <td class="px-4 py-2">date (Y-m-d)</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">ex: 2025-01-01</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].validade_meses</td>
                            <td class="px-4 py-2">int</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">ex: 12, 24</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">contratos[*].arquivo</td>
                            <td class="px-4 py-2">file (PDF)</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">requer multipart/form-data</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700">
                <strong>Observações:</strong>
                <ul class="list-disc pl-6 mt-1 space-y-1">
                    <li><code>uf</code>, <code>uf2</code> são normalizadas para maiúsculo.</li>
                    <li>Campos em <code>telefones</code> / <code>emails</code>: o service remove itens vazios e salva <code>null</code> se a lista ficar vazia.</li>
                    <li><code>cities</code>: valida “cidade ocupada” e se a cidade pertence às UFs do gestor.</li>
                    <li>Contrato/anexo com <code>ativo=true</code> aplica <code>percentual_vendas</code> e <code>vencimento_contrato</code> automaticamente.</li>
                    <li>
                        Para contrato por cidade: use <code>contratos[*].tipo=contrato_cidade</code> e informe <code>contratos[*].cidade_id</code>.
                        Recomenda-se manter a mesma cidade também em <code>cities[]</code>.
                    </li>
                </ul>
            </div>
        </div>

        {{-- CREATE (payload completo) --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar Distribuidor (payload completo)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "gestor_id": 5,

  "email": "distribuidor@empresa.com",
  "password": "12345678",

  "razao_social": "Distribuidora Beta LTDA",
  "cnpj": "12.345.678/0001-90",
  "representante_legal": "Fulano de Tal",
  "cpf": "123.456.789-00",
  "rg": "12.345.678-9",

  "emails": ["financeiro@beta.com", "comercial@beta.com"],
  "telefones": ["11999998888", "1133334444"],

  "endereco": "Rua Exemplo",
  "numero": "100",
  "complemento": "Sala 301",
  "bairro": "Centro",
  "cidade": "Vitória",
  "uf": "ES",
  "cep": "29000-000",

  "uf_cidades": "ES",
  "cities": [3427],

  "percentual_vendas": 5,

  "contratos": [
    {
      "tipo": "contrato_cidade",
      "cidade_id": 3427,
      "descricao": "Contrato de atuação — Vitória (ES)",
      "assinado": true,
      "percentual_vendas": 6,
      "ativo": true,
      "data_assinatura": "2025-01-01",
      "validade_meses": 12
    }
  ]
}</code></pre>

            <p class="text-sm text-gray-600 mt-2">
                Se enviar <code>contratos[0][arquivo]</code>, troque o request para <code>multipart/form-data</code>.
            </p>
        </div>

        {{-- UPDATE (payload completo) --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Atualizar Distribuidor (payload completo)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "gestor_id": 5,

  "email": "novoemail@empresa.com",
  "password": "12345678",

  "razao_social": "Distribuidora Beta (Atualizada)",

  "cities": [3427, 3426],

  "percentual_vendas": 7,

  "contratos": [
    {
      "tipo": "contrato_cidade",
      "cidade_id": 3426,
      "descricao": "Aditivo de atuação — Vila Velha (ES)",
      "assinado": true,
      "percentual_vendas": 8,
      "ativo": false,
      "data_assinatura": "2026-01-01",
      "validade_meses": 12
    }
  ]
}</code></pre>
        </div>

        {{-- Exemplo multipart/form-data --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Envio de contrato por cidade com arquivo (multipart/form-data)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>gestor_id: 5
razao_social: Distribuidora Beta LTDA
email: distribuidor@empresa.com
password: 12345678
cities[0]: 3427

contratos[0][tipo]: contrato_cidade
contratos[0][cidade_id]: 3427
contratos[0][descricao]: Contrato de atuação — Vitória (ES)
contratos[0][assinado]: 1
contratos[0][percentual_vendas]: 6
contratos[0][ativo]: 1
contratos[0][data_assinatura]: 2025-01-01
contratos[0][validade_meses]: 12
contratos[0][arquivo]: (PDF)</code></pre>

            <div class="rounded-md bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800 mt-3">
                <strong>Dica:</strong> No Postman, use <em>Body → form-data → Bulk Edit</em> para colar as chaves no formato
                <code>key:value</code> e depois adicione o PDF em <code>contratos[0][arquivo]</code> com o tipo <code>File</code>.
            </div>
        </div>

    </div>
</section>



{{-- ========================= --}}
{{-- ABA: PRODUTOS --}}
{{-- ========================= --}}
<section x-show="active === 'produtos'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Produtos (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                CRUD de produtos (sem endpoint show) + importação via planilha.
                Suporta upload de imagem do produto e filtros no index.
            </p>
        </div>

        {{-- Base --}}
        <div class="text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border rounded px-2 py-1 text-xs">
                /api/admin/produtos
            </code>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/produtos</td>
                        <td>Listar produtos (com busca/ordenação/paginação)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/produtos</td>
                        <td>Criar produto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/produtos/{produto}</td>
                        <td>Atualizar produto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/produtos/{produto}</td>
                        <td>Excluir produto (remove imagem do storage, se existir)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/produtos/import</td>
                        <td>Importar produtos via XLSX/XLS/CSV</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Observações importantes --}}
        <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800 space-y-2">
            <div>
                <strong>Atenção:</strong>
                requests que enviam <code>imagem</code> (produto) ou <code>arquivo</code> (import)
                devem usar <code>multipart/form-data</code>.
                Sem upload, pode usar <code>application/json</code>.
            </div>
            <div>
                <strong>Normalização:</strong> campos <code>preco</code> e <code>peso</code> aceitam formato pt-BR
                (ex: <code>"1.234,56"</code>) e serão normalizados.
            </div>
        </div>

        {{-- Query params do Index --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Index — filtros e ordenação</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li><code>q</code> (string): busca em <code>titulo</code>, <code>autores</code> e <code>colecao.nome</code> (ilike)</li>
                <li><code>sort</code> (string): <code>titulo</code>, <code>preco</code>, <code>quantidade_estoque</code>, <code>ano</code> (default: <code>titulo</code>)</li>
                <li><code>dir</code> (string): <code>asc</code> ou <code>desc</code> (default: <code>asc</code>)</li>
            </ul>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>GET /api/admin/produtos?q=matematica&sort=preco&dir=desc</code></pre>
        </div>

        {{-- Campos (rules) --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Campos do Produto</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr><td class="px-4 py-2 font-mono">codigo</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Sim</td><td class="px-4 py-2">Único, &gt;= 1</td></tr>
                        <tr><td class="px-4 py-2 font-mono">titulo</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Sim</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">isbn</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 17; deve conter 13 dígitos (com/sem traços); apenas números e traços</td></tr>
                        <tr><td class="px-4 py-2 font-mono">autores</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 255</td></tr>
                        <tr><td class="px-4 py-2 font-mono">edicao</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">max 50</td></tr>
                        <tr><td class="px-4 py-2 font-mono">ano</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 1900; max ano atual</td></tr>
                        <tr><td class="px-4 py-2 font-mono">numero_paginas</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 1</td></tr>
                        <tr><td class="px-4 py-2 font-mono">quantidade_por_caixa</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 0</td></tr>
                        <tr><td class="px-4 py-2 font-mono">peso</td><td class="px-4 py-2">numeric</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 0 (normaliza pt-BR)</td></tr>

                        <tr><td class="px-4 py-2 font-mono">ano_escolar</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">deve estar em <code>config('ano_escolar.opcoes')</code></td></tr>

                        <tr><td class="px-4 py-2 font-mono">colecao_id</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">exists: colecoes,id</td></tr>
                        <tr><td class="px-4 py-2 font-mono">descricao</td><td class="px-4 py-2">string</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">texto livre</td></tr>

                        <tr><td class="px-4 py-2 font-mono">preco</td><td class="px-4 py-2">numeric</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 0 (normaliza pt-BR)</td></tr>
                        <tr><td class="px-4 py-2 font-mono">quantidade_estoque</td><td class="px-4 py-2">int</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">min 0</td></tr>

                        <tr><td class="px-4 py-2 font-mono">imagem</td><td class="px-4 py-2">file</td><td class="px-4 py-2">Não</td><td class="px-4 py-2">png/jpg/jpeg/webp; max 2MB</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exemplo JSON (sem imagem) --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar Produto (JSON, sem imagem)
            </h3>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "codigo": 1001,
  "titulo": "Matemática • 6º ano",
  "isbn": "978-85-00000-000-0",
  "autores": "Autor 1; Autor 2",
  "edicao": "2ª",
  "ano": 2025,
  "numero_paginas": 240,
  "quantidade_por_caixa": 20,
  "peso": "1,250",
  "ano_escolar": "6º ano",
  "colecao_id": 3,
  "descricao": "Livro didático...",
  "preco": "129,90",
  "quantidade_estoque": 500
}</code></pre>
        </div>

        {{-- Exemplo multipart (com imagem) --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar/Atualizar Produto com imagem (multipart/form-data)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>codigo: 1001
titulo: Matemática • 6º ano
isbn: 978-85-00000-000-0
autores: Autor 1; Autor 2
edicao: 2ª
ano: 2025
numero_paginas: 240
quantidade_por_caixa: 20
peso: 1,250
ano_escolar: 6º ano
colecao_id: 3
descricao: Livro didático...
preco: 129,90
quantidade_estoque: 500
imagem: (arquivo .png/.jpg/.webp)</code></pre>

            <p class="text-sm text-gray-600 mt-2">
                No update, se você <strong>não</strong> enviar <code>imagem</code>, a imagem atual é mantida.
                Se enviar, a anterior é removida do storage e substituída.
            </p>
        </div>

        {{-- Importação --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">
                Importação — POST /api/admin/produtos/import
            </h3>

            <div class="text-sm text-gray-700 space-y-2">
                <p>
                    Envie um arquivo <code>.xlsx</code>, <code>.xls</code> ou <code>.csv</code> no campo <code>arquivo</code>.
                    O endpoint faz <strong>create</strong> ou <strong>update</strong> por ISBN (normalizado) e, se não achar, por <code>codigo</code>.
                </p>
                <ul class="list-disc pl-6 space-y-1">
                    <li>O arquivo deve ter colunas <strong>CÓDIGO</strong> e <strong>ISBN</strong> (obrigatórias).</li>
                    <li>Duplicidade de ISBN dentro da planilha é ignorada (conta como <code>ignoradas</code>).</li>
                    <li>Se a coluna <strong>COLEÇÃO</strong> existir, vincula por nome (match case-insensitive).</li>
                    <li><code>preco</code> e <code>peso</code> são normalizados (pt-BR).</li>
                    <li><code>ano_escolar</code> tenta casar com <code>config('ano_escolar.opcoes')</code>.</li>
                </ul>
            </div>

            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                <strong>Import:</strong> obrigatoriamente <code>multipart/form-data</code> (campo <code>arquivo</code>).
            </div>

            <h4 class="font-semibold text-gray-900">Exemplo (multipart/form-data)</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>arquivo: (planilha .xlsx/.xls/.csv)</code></pre>

            <h4 class="font-semibold text-gray-900">Resposta esperada</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "criadas": 10,
  "atualizadas": 5,
  "ignoradas": 2,
  "message": "Importação concluída. Criados: 10, Atualizados: 5, Ignorados: 2."
}</code></pre>
        </div>

    </div>
</section>


{{-- ========================= --}}
{{-- ABA: ADVOGADOS --}}
{{-- ========================= --}}
<section x-show="active === 'advogados'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Advogados (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                CRUD de advogados com criação automática de usuário (senha gerada).
                No update, sincroniza <code>nome</code> e <code>email</code> do usuário.
                Na exclusão, remove apenas o registro de Advogado (User permanece).
            </p>
        </div>

        {{-- Base --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border border-gray-200 rounded px-2 py-1 text-xs">
                /api/admin/advogados
            </code>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/advogados</td>
                        <td class="px-4 py-2">Listar advogados (paginado)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/advogados</td>
                        <td class="px-4 py-2">Criar advogado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/advogados/{advogado}</td>
                        <td class="px-4 py-2">Detalhar advogado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/advogados/{advogado}</td>
                        <td class="px-4 py-2">Atualizar advogado</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/advogados/{advogado}</td>
                        <td class="px-4 py-2">Excluir advogado</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Regras de negócio --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras de negócio</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Cria automaticamente um <strong>User</strong> com <code>name = nome</code> e <code>email</code>.</li>
                <li>A senha do usuário é gerada automaticamente (random).</li>
                <li>No <strong>update</strong>, sincroniza <code>nome</code> e <code>email</code> do usuário.</li>
                <li>No <strong>delete</strong>, remove apenas o Advogado (o <strong>User permanece</strong>).</li>
                <li><code>estado</code> é a UF (2 letras).</li>
            </ul>
        </div>

        {{-- Exemplo — Criar --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar Advogado (POST /api/admin/advogados)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "nome": "Dr. João da Silva",
  "email": "joao@exemplo.com",
  "telefone": "(11) 99999-9999",
  "percentual_vendas": 3.5,
  "oab": "SP123456",
  "logradouro": "Rua Exemplo",
  "numero": "100",
  "complemento": "Sala 301",
  "bairro": "Centro",
  "cidade": "São Paulo",
  "estado": "SP",
  "cep": "01000-000"
}</code></pre>
        </div>

        {{-- Exemplo — Atualizar --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Atualizar Advogado (PUT /api/admin/advogados/{advogado})
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "nome": "Dr. João da Silva (Atualizado)",
  "email": "joao@exemplo.com",
  "telefone": "(11) 90000-0000",
  "percentual_vendas": 4,
  "oab": "SP123456",
  "cidade": "São Paulo",
  "estado": "SP"
}</code></pre>
        </div>

    </div>
</section>


{{-- ========================= --}}
{{-- ABA: PEDIDOS --}}
{{-- ========================= --}}
<section x-show="active === 'pedidos'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Pedidos (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                Endpoints para listar, criar, detalhar e atualizar pedidos, com cálculo automático de
                totais (peso, caixas, valor bruto e valor total), validações de cidade/distribuidor e logs.
            </p>
        </div>

        {{-- Base --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border border-gray-200 rounded px-2 py-1 text-xs">
                /api/admin/pedidos
            </code>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos/create</td>
                        <td class="px-4 py-2">Payload para criação (listas: produtos, cidades, gestores, distribuidores, clientes, coleções)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos</td>
                        <td class="px-4 py-2">Listar pedidos (ordenado por mais recentes)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos</td>
                        <td class="px-4 py-2">Criar pedido (status inicial: <code>em_andamento</code>)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos/{pedido}</td>
                        <td class="px-4 py-2">Detalhar pedido (itens, pivots, cliente, gestor, distribuidor, cidades, logs e notas fiscais)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">PUT</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos/{pedido}</td>
                        <td class="px-4 py-2">Atualizar pedido (recalcula totais, valida estoque, valida cidade e registra logs)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Regras principais --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras principais</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Ao criar, o pedido nasce com <code>status = em_andamento</code>.</li>
                <li>A data do pedido (<code>data</code>) não pode ser anterior à data atual (America/Sao_Paulo).</li>
                <li>Se <code>distribuidor_id</code> for informado, <code>cidade_id</code> se torna obrigatória e deve pertencer ao distribuidor.</li>
                <li>Se não houver distribuidor, e <code>cidade_id</code> for informada, ela não pode estar ocupada por distribuidor (senão exige selecionar o distribuidor correspondente).</li>
                <li>Totais calculados automaticamente: <code>peso_total</code>, <code>total_caixas</code>, <code>valor_bruto</code>, <code>valor_total</code>.</li>
                <li>No update: não permite edição se o pedido já estiver <code>finalizado</code>.</li>
                <li>No update: valida estoque apenas se a quantidade de um produto aumentar (delta &gt; 0).</li>
                <li>No update: status permitido: <code>em_andamento</code>, <code>finalizado</code>, <code>cancelado</code>.</li>
                <li>Cancelamento: ao mudar para <code>cancelado</code>, registra log “Pedido cancelado (sem movimentação de estoque)”.</li>
            </ul>
        </div>

        {{-- Campos — Criar (POST) --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Criar Pedido (POST /api/pedidos)</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">data</td>
                            <td class="px-4 py-2">date</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">>= data atual (America/Sao_Paulo)</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cliente_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">exists: clientes,id</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">gestor_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">exists: gestores,id</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">distribuidor_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">exists: distribuidores,id</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cidade_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Condicional</td>
                            <td class="px-4 py-2">
                                Se <code>distribuidor_id</code> estiver preenchido:
                                obrigatório e a cidade deve pertencer ao distribuidor.
                                Caso contrário: se informado, deve existir em <code>cities</code> e não pode estar ocupada por distribuidor.
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos</td>
                            <td class="px-4 py-2">array</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">min: 1</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">exists: produtos,id, distinct</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].quantidade</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">min: 1</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].desconto</td>
                            <td class="px-4 py-2">number</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">0 a 100 (%)</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">observacoes</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">max 2000</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4 class="font-semibold text-gray-900 mt-4">Exemplo — Criar Pedido</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "data": "2025-12-14",
  "cliente_id": 10,
  "gestor_id": 3,
  "distribuidor_id": 5,
  "cidade_id": 120,
  "produtos": [
    { "id": 1, "quantidade": 50, "desconto": 10 },
    { "id": 2, "quantidade": 20, "desconto": 0 }
  ],
  "observacoes": "Pedido criado via API."
}</code></pre>

            <div class="text-sm text-gray-700">
                <h4 class="font-semibold text-gray-900">Mensagens de validação importantes</h4>
                <ul class="list-disc pl-6 space-y-1">
                    <li><code>data.after_or_equal</code> → “A data do pedido não pode ser anterior à data atual.”</li>
                    <li><code>cliente_id.required</code> → “Selecione um cliente.”</li>
                    <li><code>cidade_id.required</code> → “Selecione a cidade da venda (ao escolher um distribuidor).”</li>
                    <li><code>cidade_id.exists</code> → “A cidade selecionada não pertence ao distribuidor escolhido.”</li>
                </ul>
            </div>
        </div>

        {{-- Campos — Atualizar (PUT) --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Atualizar Pedido (PUT /api/pedidos/{pedido})</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">data</td>
                            <td class="px-4 py-2">date</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">>= data atual (America/Sao_Paulo)</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cliente_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">exists: clientes,id</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">cidade_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Condicional</td>
                            <td class="px-4 py-2">
                                Se o pedido tiver <code>distribuidor_id</code>, a cidade é obrigatória e deve pertencer ao distribuidor do pedido.
                                Se não houver distribuidor, e a cidade for informada, não pode estar ocupada por distribuidor.
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">status</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">in: em_andamento, finalizado, cancelado</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos</td>
                            <td class="px-4 py-2">array</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">min: 1</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">exists: produtos,id, distinct</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].quantidade</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">min: 1</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">produtos[].desconto</td>
                            <td class="px-4 py-2">number</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">0 a 100 (%)</td>
                        </tr>

                        <tr>
                            <td class="px-4 py-2 font-mono">observacoes</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">max 2000</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4 class="font-semibold text-gray-900 mt-4">Exemplo — Atualizar Pedido</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "data": "2025-12-14",
  "cliente_id": 10,
  "cidade_id": 120,
  "status": "em_andamento",
  "produtos": [
    { "id": 1, "quantidade": 60, "desconto": 10 },
    { "id": 2, "quantidade": 10, "desconto": 0 }
  ],
  "observacoes": "Ajuste de quantidades."
}</code></pre>

            <div class="text-sm text-gray-700">
                <h4 class="font-semibold text-gray-900">Erros comuns</h4>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Se o pedido estiver <code>finalizado</code>: “Não é mais possível editar: este pedido já foi finalizado.”</li>
                    <li>Se aumentar a quantidade e não houver estoque suficiente: “Estoque insuficiente para o produto X...”</li>
                    <li>Se pedido tem distribuidor e a cidade não pertence a ele: “A cidade selecionada não pertence ao distribuidor deste pedido.”</li>
                </ul>
            </div>
        </div>

        {{-- Payload do CREATE --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Payload do Create (GET /api/pedidos/create)</h3>
            <p class="text-sm text-gray-700">
                Retorna listas para o front montar a tela (produtos, cidades, gestores, distribuidores, clientes, UFs, coleções).
            </p>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "produtos": [
    { "id": 1, "titulo": "Livro X", "preco": 10.5, "imagem": "https://.../storage/...", "colecao_id": 2 }
  ],
  "colecoes": [
    { "id": 2, "nome": "Coleção A", "codigo": "A1" }
  ],
  "cidades": [
    { "id": 120, "name": "São Paulo", "state": "SP" }
  ],
  "cidadesUF": ["SP","RJ"],
  "gestores": [ ... ],
  "distribuidores": [ ... ],
  "clientes": [ ... ]
}</code></pre>
        </div>

    </div>
</section>


{{-- ========================= --}}
{{-- ABA: NOTAS FISCAIS --}}
{{-- ========================= --}}
<section x-show="active === 'notas-fiscais'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Notas Fiscais (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                Endpoints para emitir (pré-visualização), consultar, faturar e obter PDF da nota fiscal vinculada a pedidos.
            </p>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/pedidos/{pedido}/emitir-nota</td>
                        <td class="px-4 py-2">Emite uma nota (pré-visualização) para o pedido</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/notas/{nota}</td>
                        <td class="px-4 py-2">Detalha uma nota fiscal</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/notas/{nota}/faturar</td>
                        <td class="px-4 py-2">Fatura uma nota emitida (normal / simples_remessa / brinde)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/notas/{nota}/pdf</td>
                        <td class="px-4 py-2">Baixa o PDF da nota</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Regras principais --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras principais</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Não permite emitir nota para pedido com status <code>cancelado</code> ou <code>finalizado</code>.</li>
                <li>Se o pedido já tiver uma nota com status <code>faturada</code>, bloqueia nova emissão.</li>
                <li>Ao emitir uma nova nota (<code>status=emitida</code>), notas anteriores do mesmo pedido com <code>status=emitida</code> são marcadas como <code>cancelada</code> automaticamente.</li>
                <li>Ao emitir, se o pedido estiver <code>em_andamento</code>, ele é atualizado para <code>pre_aprovado</code> (e <code>status_financeiro=pre_aprovado</code>).</li>
                <li>Faturamento só é permitido se a nota estiver <code>emitida</code>.</li>
                <li>No faturamento:
                    <ul class="list-disc pl-6 mt-1 space-y-1">
                        <li><code>normal</code> → baixa estoque e define <code>status_financeiro=aguardando_pagamento</code></li>
                        <li><code>simples_remessa</code> → NÃO baixa estoque e define <code>status_financeiro=simples_remessa</code></li>
                        <li><code>brinde</code> → baixa estoque e define <code>status_financeiro=brinde</code></li>
                    </ul>
                </li>
                <li>Ao faturar, a nota vira <code>faturada</code> e o pedido é atualizado para <code>finalizado</code>.</li>
            </ul>
        </div>

        {{-- Emitir nota --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Emitir Nota (pré-visualização)</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">POST /api/admin/pedidos/{pedido}/emitir-nota</span>
            </p>

            <div class="text-sm text-gray-700 space-y-2">
                <p><span class="font-semibold">Body:</span> sem payload obrigatório.</p>
                <p>
                    Ao emitir, o sistema cria:
                    <ul class="list-disc pl-6 mt-1 space-y-1">
                        <li><code>nota_fiscal</code> com <code>status=emitida</code> e snapshots (emitente, destinatário, pedido)</li>
                        <li><code>nota_itens</code> com base nos produtos do pedido (quantidade, subtotal, caixas, peso etc.)</li>
                    </ul>
                </p>
            </div>

            <h4 class="font-semibold text-gray-900 mt-4">Resposta — Exemplo (sucesso)</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "nota": {
    "id": 55,
    "pedido_id": 120,
    "numero": "102",
    "serie": "1",
    "status": "emitida",
    "status_financeiro": "pre_aprovado",
    "valor_bruto": 1500,
    "desconto_total": 150,
    "valor_total": 1350,
    "emitida_em": "2025-12-14T18:10:00-03:00"
  }
}</code></pre>

            <div class="text-sm text-gray-700">
                <h4 class="font-semibold text-gray-900">Erros comuns (regras do service)</h4>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Pedido cancelado/finalizado → “Não é possível emitir pré-visualização para pedidos cancelados/finalizados.”</li>
                    <li>Pedido já possui nota faturada → “Este pedido já possui uma nota faturada. Não é possível emitir outra.”</li>
                </ul>
            </div>
        </div>

        {{-- Show nota --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Detalhar Nota</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">GET /api/admin/notas/{nota}</span>
            </p>

            <p class="text-sm text-gray-700">
                Retorna a nota e, normalmente, seus itens e vínculo com pedido (depende do controller, mas o service trabalha com <code>nota->itens</code> e <code>nota->pedido</code>).
            </p>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "nota": {
    "id": 55,
    "pedido_id": 120,
    "numero": "102",
    "status": "emitida",
    "valor_total": 1350
  },
  "itens": [
    {
      "produto_id": 1,
      "quantidade": 50,
      "preco_unitario": 10,
      "subtotal": 450
    }
  ]
}</code></pre>
        </div>

        {{-- Faturar --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Faturar Nota</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">POST /api/admin/notas/{nota}/faturar</span>
            </p>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Regras / Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">modo</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">
                                <code>normal</code> | <code>simples_remessa</code> | <code>brinde</code>.
                                Se vier diferente, assume <code>normal</code>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4 class="font-semibold text-gray-900 mt-4">Exemplo — Faturar (normal)</h4>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "modo": "normal"
}</code></pre>

            <div class="text-sm text-gray-700">
                <h4 class="font-semibold text-gray-900">O que acontece no faturamento</h4>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Valida que a nota está em <code>emitida</code>.</li>
                    <li>Valida existência de todos os produtos dos itens.</li>
                    <li>Se <code>modo</code> for <code>normal</code> ou <code>brinde</code>: baixa estoque (com validação de saldo).</li>
                    <li>Atualiza nota para <code>status=faturada</code>, preenche <code>faturada_em</code> e ajusta <code>status_financeiro</code>.</li>
                    <li>Atualiza o pedido para <code>status=finalizado</code> e registra log.</li>
                </ul>
            </div>

            <div class="text-sm text-gray-700">
                <h4 class="font-semibold text-gray-900">Erros comuns (regras do service)</h4>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Nota não está “emitida” → “A nota não está no status correto para faturamento.”</li>
                    <li>Produto do item não encontrado → “Produto X não encontrado.”</li>
                    <li>Estoque insuficiente → “Estoque insuficiente para {nome}.”</li>
                </ul>
            </div>
        </div>

        {{-- PDF --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">PDF da Nota</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">GET /api/admin/notas/{nota}/pdf</span>
            </p>

            <p class="text-sm text-gray-700">
                Retorna o PDF da nota (download/stream conforme implementação do controller).
            </p>

            <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                <strong>Dica:</strong> no Postman, verifique o response type (arquivo) e os headers (<code>Content-Type: application/pdf</code>).
            </div>
        </div>

    </div>
</section>

{{-- ========================= --}}
{{-- ABA: PAGAMENTOS DA NOTA --}}
{{-- ========================= --}}
<section x-show="active === 'pagamentos-nota'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Pagamentos da Nota Fiscal
            </h2>
            <p class="text-sm text-gray-500">
                Endpoints para registrar, consultar e preparar pagamentos vinculados a notas fiscais faturadas.
            </p>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/notas/{nota}/pagamentos/create</td>
                        <td class="px-4 py-2">Retorna dados necessários para registrar pagamento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/notas/{nota}/pagamentos</td>
                        <td class="px-4 py-2">Registra um pagamento da nota</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/notas/{nota}/pagamentos/{pagamento}</td>
                        <td class="px-4 py-2">Detalha um pagamento específico</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Regras principais --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras principais</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Apenas notas com status <code>faturada</code> podem registrar pagamentos.</li>
                <li>O valor líquido é calculado automaticamente com base nas retenções.</li>
                <li>As comissões são calculadas sobre o <strong>valor líquido</strong>.</li>
                <li>Percentuais de gestor/distribuidor seguem prioridade:
                    <ol class="list-decimal pl-6 mt-1">
                        <li>Contrato por cidade vigente</li>
                        <li>Contrato global ativo vigente</li>
                        <li>Contrato global vigente mais recente</li>
                        <li>Percentual cadastrado no usuário</li>
                    </ol>
                </li>
                <li>Ao registrar pagamento, o <code>status_financeiro</code> da nota é atualizado automaticamente.</li>
            </ul>
        </div>

        {{-- CREATE --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Payload de Criação</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">GET /api/notas/{nota}/pagamentos/create</span>
            </p>

            <p class="text-sm text-gray-700">
                Retorna todos os dados necessários para montar a tela de pagamento:
            </p>

            <ul class="list-disc pl-6 text-sm text-gray-700">
                <li>Nota fiscal e pedido</li>
                <li>Cidades do pedido</li>
                <li>Anexos (contratos) de gestor e distribuidor</li>
                <li>Percentuais resolvidos por cidade</li>
                <li>Lista de advogados</li>
                <li>Lista de diretores comerciais</li>
            </ul>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "nota": { "id": 10, "status": "faturada" },
  "advogados": [
    { "id": 1, "nome": "Dr. João", "percentual_vendas": 5 }
  ],
  "diretores": [
    { "id": 2, "nome": "Carlos", "percentual_vendas": 3 }
  ],
  "percGestor": 10,
  "percDistribuidor": 8
}</code></pre>
        </div>

        {{-- STORE --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Registrar Pagamento</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">POST /api/notas/{nota}/pagamentos</span>
            </p>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">valor_pago</td>
                            <td class="px-4 py-2">numeric</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">Valor bruto recebido</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">data_pagamento</td>
                            <td class="px-4 py-2">date</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">Data do pagamento</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">ret_irrf / iss / inss / pis / cofins / csll / outros</td>
                            <td class="px-4 py-2">numeric (%)</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">Percentuais de retenção</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">adesao_ata</td>
                            <td class="px-4 py-2">boolean</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">Exige advogado</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">advogado_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Condicional</td>
                            <td class="px-4 py-2">Obrigatório se adesão à ata</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">diretor_id</td>
                            <td class="px-4 py-2">integer</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">Diretor comercial</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">observacoes</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">Até 2000 caracteres</td>
                        </tr>
                    </tbody>
                </table>
            </div>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "data_pagamento": "2025-12-10",
  "valor_pago": 10000,
  "ret_irrf": 1.5,
  "ret_iss": 2,
  "adesao_ata": true,
  "advogado_id": 1,
  "observacoes": "Pagamento via TED"
}</code></pre>
        </div>

        {{-- SHOW --}}
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900">Detalhar Pagamento</h3>
            <p class="text-sm text-gray-700">
                <span class="font-mono">GET /api/notas/{nota}/pagamentos/{pagamento}</span>
            </p>

            <p class="text-sm text-gray-700">
                Retorna o pagamento com todos os cálculos consolidados:
            </p>

            <ul class="list-disc pl-6 text-sm text-gray-700">
                <li>Valor bruto, líquido e retenções</li>
                <li>Comissões (gestor, distribuidor, advogado, diretor)</li>
                <li>Percentuais utilizados (snapshot)</li>
                <li>Resumo financeiro do pedido</li>
            </ul>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "pagamento": {
    "id": 5,
    "valor_pago": 10000,
    "valor_liquido": 9300
  },
  "comissao_gestor": 930,
  "comissao_distribuidor": 744,
  "comissao_advogado": 465,
  "total_retencoes": 700
}</code></pre>

    </div>
</section>

{{-- ========================= --}}
{{-- ABA: COLEÇÕES --}}
{{-- ========================= --}}
<section x-show="active === 'colecoes'" x-cloak>
    <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-8">

        {{-- Cabeçalho --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Coleções (Admin)
            </h2>
            <p class="text-sm text-gray-500">
                Endpoints para listar e manter coleções usadas em Produtos e no payload do “Criar Pedido”.
                Inclui endpoint de criação rápida (quick-create).
            </p>
        </div>

        {{-- Base --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Base:</span>
            <code class="bg-gray-100 border border-gray-200 rounded px-2 py-1 text-xs">
                /api/admin/colecoes
            </code>
        </div>

        {{-- Endpoints --}}
        <div class="overflow-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-left">Endpoint</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 font-mono">GET</td>
                        <td class="px-4 py-2 font-mono">/api/admin/colecoes</td>
                        <td class="px-4 py-2">Listar coleções (para selects e filtros)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">POST</td>
                        <td class="px-4 py-2 font-mono">/api/admin/colecoes/quick-create</td>
                        <td class="px-4 py-2">Criar coleção rapidamente (retorna a coleção criada)</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2 font-mono">DELETE</td>
                        <td class="px-4 py-2 font-mono">/api/admin/colecoes/{colecao}</td>
                        <td class="px-4 py-2">Excluir coleção</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Regras de negócio --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Regras de negócio</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>nome</strong> é obrigatório na criação rápida.</li>
                
                <li>Coleções aparecem em:
                    <ul class="list-disc pl-6 mt-1 space-y-1">
                        <li><code>Produtos</code> (campo <code>colecao_id</code>)</li>
                        <li><code>Pedidos/create</code> (lista <code>colecoes</code> para filtros/seleção)</li>
                    </ul>
                </li>
            </ul>
        </div>

        {{-- Campos quick-create --}}
        <div class="space-y-2 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900">Quick-create — Campos</h3>

            <div class="overflow-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Campo</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Obrigatório</th>
                            <th class="px-4 py-2 text-left">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 font-mono">nome</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Sim</td>
                            <td class="px-4 py-2">max 255</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono">codigo</td>
                            <td class="px-4 py-2">string</td>
                            <td class="px-4 py-2">Não</td>
                            <td class="px-4 py-2">opcional (se você usa no seu model)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exemplo — Listar --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Listar Coleções (GET /api/admin/colecoes)
            </h3>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>[
  { "id": 1, "nome": "Coleção Alfa", "codigo": "ALFA" },
  { "id": 2, "nome": "Coleção Beta", "codigo": "BETA" }
]</code></pre>
        </div>

        {{-- Exemplo — Quick create --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Criar Coleção (POST /api/admin/colecoes/quick-create)
            </h3>

<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "nome": "Coleção 2026",
  "codigo": "C26"
}</code></pre>

            <p class="text-sm text-gray-700 mt-2">Resposta esperada (201/200):</p>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true,
  "colecao": { "id": 10, "nome": "Coleção 2026", "codigo": "C26" }
}</code></pre>
        </div>

        {{-- Exemplo — Delete --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">
                Exemplo — Excluir Coleção (DELETE /api/admin/colecoes/{colecao})
            </h3>
<pre class="text-xs bg-gray-900 text-white rounded-lg p-4 overflow-auto"><code>{
  "ok": true
}</code></pre>

        </div>

    </div>
</section>



            <section x-show="active === 'anexos'" x-cloak>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Anexos</h2>
                    <p class="text-sm text-gray-500">Documentação em construção.</p>
                </div>
            </section>

            <section x-show="active === 'cidades'" x-cloak>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Cidades</h2>
                    <p class="text-sm text-gray-500">Documentação em construção.</p>
                </div>
            </section>

        </div>
    </div>

    {{-- Alpine --}}
    <script>
        function docsTabs() {
            return {
                active: 'overview',
                tabs: [                    
                    { key: 'overview', label: 'Visão geral' },
                    { key: 'auth', label: 'Autenticação' },
                    { key: 'clientes', label: 'Clientes' },                    
                    { key: 'gestores', label: 'Gestores' },
                    { key: 'distribuidores', label: 'Distribuidores' },
                    { key: 'advogados', label: 'Advogados' },
                    { key: 'diretor-comercials', label: 'Diretor Comercial' },
                    { key: 'produtos', label: 'Produtos' },
                    { key: 'pedidos', label: 'Pedidos' }, 
                    { key: 'notas-fiscais', label: 'Notas Fiscais' },    
                    { key: 'pagamentos-nota', label: 'Pagamentos da Nota' },   
                    { key: 'colecoes', label: 'Coleções' },
           
                ],
                setActive(tab) {
                    this.active = tab;
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

</x-app-layout>
