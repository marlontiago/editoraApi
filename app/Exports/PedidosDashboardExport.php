<?php

namespace App\Exports;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // (1) NOVO: precisamos do DB::raw
use Maatwebsite\Excel\Concerns\{FromQuery,WithHeadings,WithMapping,ShouldAutoSize,WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Log;

class PedidosDashboardExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    public function __construct(private Request $request) {}

    public function query()
    {
        // Monta a query idêntica à do dashboard
        $q = Pedido::with(['gestor.user:id,name','distribuidor.user:id,name','cidades:id,name']);

        $dataInicio     = $this->request->input('data_inicio');
        $dataFim        = $this->request->input('data_fim');
        $gestorId       = $this->request->input('gestor_id');
        $distribuidorId = $this->request->input('distribuidor_id');
        $status         = $this->request->input('status');
        
        if ($dataInicio && $dataFim) {
            $q->whereBetween('data', [
                \Carbon\Carbon::parse($dataInicio)->toDateString(),
                \Carbon\Carbon::parse($dataFim)->toDateString()
            ]);
        } elseif ($dataInicio) {
            $q->where('data', '>=', \Carbon\Carbon::parse($dataInicio)->toDateString());
        } elseif ($dataFim) {
            $q->where('data', '<=', \Carbon\Carbon::parse($dataFim)->toDateString());
        }

        if ($gestorId)       $q->where('gestor_id', $gestorId);
        if ($distribuidorId) $q->where('distribuidor_id', $distribuidorId);

        
        if ($status) {
            $statusNorm = strtolower(trim($status));
            $aliases = [
                'em_andamento' => ['em_andamento'],
                'cancelado'    => ['cancelado', 'cancelada'],
                'finalizado'   => ['finalizado', 'finalizada'],
            ];
            $valoresAceitos = $aliases[$statusNorm] ?? [$statusNorm];

            $q->whereIn(
                DB::raw('LOWER(TRIM(status))'),
                array_map(fn($v) => strtolower(trim($v)), $valoresAceitos)
            );
        }

        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'ID do Pedido',
            'Data',
            'Gestor',
            'Distribuidor',
            'Cidades (atuacao)',
            'Status',
            'Valor Total (R$)',
        ];
    }

    public function map($pedido): array
    {
        $gestor       = optional($pedido->gestor?->user)->name ?? '-';
        $distribuidor = optional($pedido->distribuidor?->user)->name ?? '-';
        $cidades      = $pedido->cidades?->pluck('name')->join(', ') ?: '-';

        $statusLabel = match ($pedido->status) {
            'em_andamento' => 'Em andamento',
            'finalizado'   => 'Finalizado',
            'cancelado'    => 'Cancelado',
            default        => $pedido->status,
        };

        // Se $pedido->data já for Carbon (cast), isso funciona; senão, adapte:
        $dataFormatada = $pedido->data instanceof \Carbon\Carbon
            ? $pedido->data->format('d/m/Y')
            : (filled($pedido->data) ? \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') : '—');

        return [
            $pedido->id,
            $dataFormatada,
            $gestor,
            $distribuidor,
            $cidades,
            $statusLabel,
            number_format((float)$pedido->valor_total, 2, ',', '.'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
