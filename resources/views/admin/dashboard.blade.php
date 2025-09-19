<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800">Dashboard</h2>

            {{-- BOTÕES DE EXPORTAÇÃO (desktop) --}}
            <div class="hidden sm:flex gap-2">
                {{-- OBS: nomes de rota conforme seu routes (admin.admin... nas exportações) --}}
                <a href="#"
                   data-export
                   data-base="{{ route('admin.admin.dashboard.export.excel') }}"
                   class="px-3 py-1.5 text-sm rounded border">Exportar Excel</a>

                <a href="#"
                   data-export
                   data-base="{{ route('admin.admin.dashboard.export.pdf') }}"
                   class="px-3 py-1.5 text-sm rounded border">Exportar PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        {{-- Chips de filtros ativos --}}
        <div class="flex flex-wrap gap-2 text-xs">
            @if($dataInicio || $dataFim)
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border">
                    Período:
                    <b>{{ $dataInicio ? \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') : '—' }}</b>
                    <span>→</span>
                    <b>{{ $dataFim ? \Carbon\Carbon::parse($dataFim)->format('d/m/Y') : '—' }}</b>
                </span>
            @endif
            @if($gestorId)
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border">
                    Gestor:
                    <b>{{ optional($gestoresList->firstWhere('id',$gestorId)->user)->name ?? $gestoresList->firstWhere('id',$gestorId)->razao_social }}</b>
                </span>
            @endif
            @if($distribuidorId)
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border">
                    Distribuidor:
                    <b>{{ optional($distribuidoresList->firstWhere('id',$distribuidorId)->user)->name ?? $distribuidoresList->firstWhere('id',$distribuidorId)->razao_social }}</b>
                </span>
            @endif
            @if($status)
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border">
                    Status: <b>{{ ucfirst(str_replace('_',' ',$status)) }}</b>
                </span>
            @endif
            @if(!$dataInicio && !$dataFim && !$gestorId && !$distribuidorId && !$status)
                <span class="text-gray-400">Sem filtros</span>
            @endif
        </div>

        {{-- Filtros (colapsáveis) --}}
        <details class="rounded-xl border" {{ ($dataInicio||$dataFim||$gestorId||$distribuidorId||$status) ? 'open' : '' }}>
            <summary class="p-3 text-sm font-medium cursor-pointer flex items-center justify-between">
                <span>Filtros</span>
                <span class="text-xs text-gray-500">Clique para {{ ($dataInicio||$dataFim||$gestorId||$distribuidorId||$status) ? 'ocultar' : 'abrir' }}</span>
            </summary>
            <div class="border-t p-3">
                <form id="filtros" method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data Início</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full rounded border-gray-300 h-9 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data Fim</label>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full rounded border-gray-300 h-9 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Gestor</label>
                        <select name="gestor_id" class="w-full rounded border-gray-300 h-9 text-sm">
                            <option value="">Todos</option>
                            @foreach($gestoresList as $g)
                                <option value="{{ $g->id }}" @selected($gestorId==$g->id)>
                                    {{ $g->user->name ?? $g->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Distribuidor</label>
                        <select name="distribuidor_id" class="w-full rounded border-gray-300 h-9 text-sm">
                            <option value="">Todos</option>
                            @foreach($distribuidoresList as $d)
                                <option value="{{ $d->id }}" @selected($distribuidorId==$d->id)>
                                    {{ $d->user->name ?? $d->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status Pedido</label>
                        <select name="status" class="w-full rounded border-gray-300 h-9 text-sm">
                            <option value="">Todos</option>
                            <option value="em_andamento" @selected($status==='em_andamento')>Em andamento</option>
                            <option value="finalizado" @selected($status==='finalizado')>Finalizado</option>
                            <option value="cancelado" @selected($status==='cancelado')>Cancelado</option>
                        </select>
                    </div>

                    <div class="md:col-span-5 flex flex-wrap gap-2 pt-1">
                        <button class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm">Aplicar</button>
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded border text-sm">Limpar</a>

                        {{-- BOTÕES DE EXPORTAÇÃO (mobile) --}}
                        <span class="sm:hidden inline-flex gap-2">
                            <a href="#"
                               data-export
                               data-base="{{ route('admin.admin.dashboard.export.excel') }}"
                               class="px-3 py-1.5 rounded border text-sm">Excel</a>

                            <a href="#"
                               data-export
                               data-base="{{ route('admin.admin.dashboard.export.pdf') }}"
                               class="px-3 py-1.5 rounded border text-sm">PDF</a>
                        </span>
                    </div>
                </form>
            </div>
        </details>

        {{-- GRÁFICOS --}}
        <div class="space-y-4">
            {{-- Linha (100% largura) --}}
            <div class="rounded-xl border p-3 h-[360px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-sm">Notas pagas por mês</h3>
                    <span class="text-[11px] text-gray-500">quantidade e valor</span>
                </div>
                <div class="h-[300px]">
                    <canvas id="chartNotasPagas"></canvas>
                </div>
            </div>

            {{-- Top 3 doughnuts --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border p-3 h-[280px]">
                    <h3 class="font-semibold text-sm mb-2">Vendas por Gestor</h3>
                    <div class="h-[220px]"><canvas id="chartVendasPorGestor"></canvas></div>
                </div>
                <div class="rounded-xl border p-3 h-[280px]">
                    <h3 class="font-semibold text-sm mb-2">Vendas por Distribuidor</h3>
                    <div class="h-[220px]"><canvas id="chartVendasPorDistribuidor"></canvas></div>
                </div>
                <div class="rounded-xl border p-3 h-[280px]">
                    <h3 class="font-semibold text-sm mb-2">Vendas por Cliente</h3>
                    <div class="h-[220px]"><canvas id="chartVendasPorCliente"></canvas></div>
                </div>
            </div>

            {{-- Cidades: barra horizontal --}}
            <div class="rounded-xl border p-3 h-[460px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-sm">Vendas por Cidade</h3>
                    <span class="text-[11px] text-gray-500">Top 15 + “Outras”</span>
                </div>
                <div class="h-[400px]"><canvas id="chartVendasPorCidade"></canvas></div>
            </div>
        </div>

        {{-- LISTA de pedidos --}}
        <div class="rounded-xl border p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-sm">Pedidos</h3>
                <div class="text-[11px] text-gray-500">
                    Página: <b>R$ {{ number_format($somaPagina,2,',','.') }}</b> ·
                    Geral: <b>R$ {{ number_format($somaGeralTodosPedidos,2,',','.') }}</b>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="sticky top-0 bg-white">
                        <tr class="text-left border-b">
                            <th class="py-2">#</th>
                            <th>Data</th>
                            <th>Gestor</th>
                            <th>Distribuidor</th>
                            <th>Status</th>
                            <th class="text-right">Valor total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidos as $p)
                            <tr class="border-b">
                                <td class="py-2">{{ $p->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->data)->format('d/m/Y') }}</td>
                                <td>{{ $p->gestor->user->name ?? $p->gestor->razao_social ?? '—' }}</td>
                                <td>{{ $p->distribuidor->user->name ?? $p->distribuidor->razao_social ?? '—' }}</td>
                                <td>{{ ucfirst(str_replace('_',' ',$p->status)) }}</td>
                                <td class="text-right">R$ {{ number_format($p->valor_total,2,',','.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $pedidos->links() }}
            </div>
        </div>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      function qs(form) {
        const params = new URLSearchParams(new FormData(form));
        return '?' + params.toString();
      }

      // exportações: anexa filtros atuais à rota base
      document.addEventListener('click', (e) => {
        const a = e.target.closest('a[data-export]');
        if (!a) return;
        e.preventDefault();
        const form = document.getElementById('filtros');
        const query = form ? qs(form) : '';
        const url = a.getAttribute('data-base') + query;
        window.location.href = url;
      });

      let gNotas, gGestor, gDistribuidor, gCliente, gCidade;

      const BRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
      function groupTopN(labels, series, n = 15, outrasLabel = 'Outras') {
        const arr = labels.map((l, i) => ({ l, v: Number(series[i] || 0) }))
                          .sort((a,b) => b.v - a.v);
        const top = arr.slice(0, n);
        const resto = arr.slice(n);
        const outras = resto.reduce((s, r) => s + r.v, 0);
        if (outras > 0) top.push({ l: outrasLabel, v: outras });
        return { labels: top.map(x=>x.l), series: top.map(x=>x.v) };
      }

      async function loadCharts() {
        const form = document.getElementById('filtros');
        const q = qs(form);

        // === ENDPOINTS (usando nomes de rota que você tem hoje) ===
        const endpoints = {
          notas:  "{{ route('admin.dashboard.charts.notas_pagas') }}",
          gestor: "{{ route('admin.dashboard.charts.vendas_por_gestor') }}",
          distr:  "{{ route('admin.dashboard.charts.vendas_por_distribuidor') }}",
          cliente:"{{ route('admin.dashboard.charts.vendas_por_cliente') }}",
          cidade: "{{ route('admin.dashboard.charts.vendas_por_cidade') }}",
        };

        let notas, gestor, distr, porCliente, porCidade;
        try {
          [notas, gestor, distr, porCliente, porCidade] = await Promise.all([
            fetch(endpoints.notas   + q).then(r => r.json()),
            fetch(endpoints.gestor  + q).then(r => r.json()),
            fetch(endpoints.distr   + q).then(r => r.json()),
            fetch(endpoints.cliente + q).then(r => r.json()),
            fetch(endpoints.cidade  + q).then(r => r.json()),
          ]);
        } catch (e) {
          console.error('Falha ao carregar dados dos gráficos', e);
          return;
        }

        // ---- Linha: Notas pagas
        const ctxNotas = document.getElementById('chartNotasPagas').getContext('2d');
        if (gNotas) gNotas.destroy();
        gNotas = new Chart(ctxNotas, {
          type: 'line',
          data: {
            labels: notas.labels,
            datasets: [
              {
                label: 'Quantidade',
                data: notas.series.quantidade,
                yAxisID: 'y1',
                borderWidth: 2,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.15)',
                fill: false,
                tension: 0.25,
                spanGaps: true,
                pointRadius: 2
              },
              {
                label: 'Valor (R$)',
                data: notas.series.valor,
                yAxisID: 'y2',
                borderWidth: 2,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,0.15)',
                fill: false,
                tension: 0.25,
                spanGaps: true,
                pointRadius: 2
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
              legend: { display: true },
              tooltip: {
                callbacks: {
                  label: (ctx) => {
                    const dsLabel = ctx.dataset.label || '';
                    const v = ctx.raw ?? 0;
                    return dsLabel.includes('Valor') ? `${dsLabel}: ${BRL.format(v)}` : `${dsLabel}: ${v}`;
                  }
                }
              }
            },
            scales: {
              x: { ticks: { autoSkip: true, maxTicksLimit: 13 } },
              y1: { type: 'linear', position: 'left', beginAtZero: true, title: { display: true, text: 'Qtd' } },
              y2: { type: 'linear', position: 'right', beginAtZero: true, title: { display: true, text: 'R$' }, grid: { drawOnChartArea: false } }
            },
            elements: { line: { borderJoinStyle: 'round' } }
          }
        });

        // ---- Doughnuts
        const pieOpts = {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${BRL.format(ctx.raw ?? 0)}` } },
          }
        };

        // Gestor
        const ctxGestor = document.getElementById('chartVendasPorGestor').getContext('2d');
        if (gGestor) gGestor.destroy();
        gGestor = new Chart(ctxGestor, {
          type: 'doughnut',
          data: { labels: gestor.labels, datasets: [{ data: gestor.series }] },
          options: pieOpts
        });

        // Distribuidor
        const ctxDistr = document.getElementById('chartVendasPorDistribuidor').getContext('2d');
        if (gDistribuidor) gDistribuidor.destroy();
        gDistribuidor = new Chart(ctxDistr, {
          type: 'doughnut',
          data: { labels: distr.labels, datasets: [{ data: distr.series }] },
          options: pieOpts
        });

        // Cliente — Top 12 + Outras
        const cliGrouped = groupTopN(porCliente.labels, porCliente.series, 12);
        const ctxCli = document.getElementById('chartVendasPorCliente').getContext('2d');
        if (gCliente) gCliente.destroy();
        gCliente = new Chart(ctxCli, {
          type: 'doughnut',
          data: { labels: cliGrouped.labels, datasets: [{ data: cliGrouped.series }] },
          options: pieOpts
        });

        // Cidade — Top 15 + Outras (barra horizontal)
        const cidGrouped = groupTopN(porCidade.labels, porCidade.series, 15);
        const ctxCid = document.getElementById('chartVendasPorCidade').getContext('2d');
        if (gCidade) gCidade.destroy();
        gCidade = new Chart(ctxCid, {
          type: 'bar',
          data: {
            labels: cidGrouped.labels,
            datasets: [{ label: 'Valor (R$)', data: cidGrouped.series }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: { callbacks: { label: (ctx) => BRL.format(ctx.raw ?? 0) } }
            },
            scales: {
              x: { ticks: { callback: (v) => BRL.format(v) }, beginAtZero: true },
              y: { beginAtZero: true }
            }
          }
        });
      }

      document.addEventListener('DOMContentLoaded', loadCharts);
    </script>
</x-app-layout>
