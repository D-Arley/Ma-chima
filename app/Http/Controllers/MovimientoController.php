<?php

namespace App\Http\Controllers;

use App\Models\CajaMovimiento;
use App\Models\InventoryMovement;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimientoController extends Controller
{
    public function index(Request $request): View
    {
        ['desde' => $desde, 'hasta' => $hasta, 'buscar' => $buscar] = $this->validatedFilters($request);
        $allMovimientos = $this->collectMovements($desde, $hasta, $buscar);
        $perPage = 100;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $movimientos = new LengthAwarePaginator(
            $allMovimientos->slice(($currentPage - 1) * $perPage, $perPage)->values(),
            $allMovimientos->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $ventasDia = Venta::when($desde, fn ($query) => $query->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('fecha', '<=', $hasta))
            ->get();

        $resumen = [
            'ventas' => $ventasDia->sum('total'),
            'ganancia' => $ventasDia->sum('ganancia'),
            'iva' => $ventasDia->sum('iva'),
            'transacciones' => $ventasDia->count(),
        ];

        $semanal = collect();
        for ($day = 6; $day >= 0; $day--) {
            $fecha = today()->subDays($day);
            $semanal->push([
                'dia' => $fecha->translatedFormat('D'),
                'fecha' => $fecha->format('d/m'),
                'total' => (float) Venta::whereDate('fecha', $fecha)->sum('total'),
                'ganancia' => (float) Venta::whereDate('fecha', $fecha)->sum('ganancia'),
            ]);
        }

        return view('movimientos.index', compact('movimientos', 'resumen', 'desde', 'hasta', 'buscar', 'semanal'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        ['desde' => $desde, 'hasta' => $hasta, 'buscar' => $buscar] = $this->validatedFilters($request);
        $movimientos = $this->collectMovements($desde, $hasta, $buscar);
        $filename = 'movimientos-ma-chima-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($movimientos) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Fecha', 'Tipo', 'Detalle', 'Referencia', 'Usuario', 'Entrada', 'Salida', 'Valor', 'Motivo']);
            foreach ($movimientos as $movimiento) {
                fputcsv($output, [
                    $movimiento['fecha']->format('Y-m-d H:i'),
                    $movimiento['tipo'],
                    $movimiento['detalle'],
                    $movimiento['referencia'],
                    $movimiento['usuario'],
                    $movimiento['entrada'],
                    $movimiento['salida'],
                    $movimiento['valor'],
                    $movimiento['motivo'],
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        ['desde' => $desde, 'hasta' => $hasta, 'buscar' => $buscar] = $this->validatedFilters($request);
        $movimientos = $this->collectMovements($desde, $hasta, $buscar);
        $filename = 'movimientos-ma-chima-'.now()->format('Y-m-d-His').'.xls';
        $periodo = ($desde?->format('d/m/Y') ?? 'Primer registro').' - '.($hasta?->format('d/m/Y') ?? 'Hoy');
        $criterio = $buscar !== '' ? $buscar : 'Sin búsqueda';
        $ventas = $movimientos->where('tipo', 'Venta');
        $resumen = [
            'Registros exportados' => $movimientos->count(),
            'Transacciones de venta' => $ventas->count(),
            'Ventas registradas (COP)' => $ventas->sum('valor'),
            'Descuentos aplicados (COP)' => $ventas->sum('descuento'),
            'IVA de ventas (COP)' => $ventas->sum('iva'),
            'Ganancia neta de ventas (COP)' => $ventas->sum('ganancia'),
            'Unidades vendidas' => $ventas->sum('salida'),
            'Unidades recibidas en inventario' => $movimientos->where('tipo', 'Inventario')->sum('entrada'),
            'Unidades descontadas del inventario' => $movimientos->where('tipo', 'Inventario')->sum('salida'),
            'Entradas de caja (COP)' => $movimientos->where('tipo', 'Caja')->sum('entrada'),
            'Salidas de caja (COP)' => $movimientos->where('tipo', 'Caja')->sum('salida'),
        ];

        return response()->streamDownload(function () use ($movimientos, $periodo, $criterio, $resumen): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles>';
            echo '<Style ss:ID="Title"><Alignment ss:Vertical="Center"/><Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF"/><Interior ss:Color="#171511" ss:Pattern="Solid"/></Style>';
            echo '<Style ss:ID="Section"><Font ss:Bold="1" ss:Size="12" ss:Color="#171511"/><Interior ss:Color="#E4C577" ss:Pattern="Solid"/></Style>';
            echo '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#302A20" ss:Pattern="Solid"/><Alignment ss:Vertical="Center" ss:WrapText="1"/></Style>';
            echo '<Style ss:ID="Body"><Alignment ss:Vertical="Center" ss:WrapText="1"/></Style>';
            echo '<Style ss:ID="Number"><NumberFormat ss:Format="#,##0"/></Style>';
            echo '<Style ss:ID="Money"><NumberFormat ss:Format="&quot;COP $&quot; #,##0"/></Style>';
            echo '<Style ss:ID="Total"><Font ss:Bold="1"/><Interior ss:Color="#F2EAD8" ss:Pattern="Solid"/></Style>';
            echo '</Styles>';

            echo '<Worksheet ss:Name="Resumen"><Table>';
            echo '<Column ss:Width="250"/><Column ss:Width="170"/>';
            echo $this->excelRow([['value' => "Ma'chima | Informe de movimientos", 'style' => 'Title'], ['value' => '', 'style' => 'Title']]);
            echo $this->excelRow([['value' => 'Periodo consultado', 'style' => 'Section'], ['value' => $periodo, 'style' => 'Body']]);
            echo $this->excelRow([['value' => 'Búsqueda aplicada', 'style' => 'Section'], ['value' => $criterio, 'style' => 'Body']]);
            echo $this->excelRow([['value' => 'Generado', 'style' => 'Section'], ['value' => now()->format('d/m/Y H:i:s'), 'style' => 'Body']]);
            echo $this->excelRow([]);
            echo $this->excelRow([['value' => 'Indicador', 'style' => 'Header'], ['value' => 'Resultado', 'style' => 'Header']]);

            foreach ($resumen as $etiqueta => $valor) {
                $esDinero = str_contains($etiqueta, 'COP');
                echo $this->excelRow([
                    ['value' => $etiqueta, 'style' => 'Body'],
                    ['value' => $valor, 'type' => 'Number', 'style' => $esDinero ? 'Money' : 'Number'],
                ]);
            }

            echo '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><PageSetup><Layout x:Orientation="Portrait"/></PageSetup><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight></Print></WorksheetOptions></Worksheet>';

            echo '<Worksheet ss:Name="Movimientos"><Table>';
            echo '<Column ss:Width="120"/><Column ss:Width="90"/><Column ss:Width="240"/><Column ss:Width="150"/><Column ss:Width="150"/>';
            echo '<Column ss:Width="100"/><Column ss:Width="100"/><Column ss:Width="130"/><Column ss:Width="130"/><Column ss:Width="130"/>';
            echo '<Column ss:Width="130"/><Column ss:Width="150"/><Column ss:Width="130"/><Column ss:Width="220"/>';
            echo $this->excelRow([['value' => "Ma'chima | Detalle de movimientos", 'style' => 'Title']]);
            echo $this->excelRow([['value' => 'Periodo consultado', 'style' => 'Section'], ['value' => $periodo, 'style' => 'Body']]);
            echo $this->excelRow([['value' => 'Búsqueda aplicada', 'style' => 'Section'], ['value' => $criterio, 'style' => 'Body']]);
            echo $this->excelRow([]);
            echo $this->excelRow(array_map(
                fn (string $titulo): array => ['value' => $titulo, 'style' => 'Header'],
                ['Fecha', 'Tipo', 'Producto / detalle', 'Referencia', 'Usuario', 'Unidades de entrada', 'Unidades de salida', 'Entrada de caja (COP)', 'Salida de caja (COP)', 'Valor registrado (COP)', 'IVA de venta (COP)', 'Ganancia neta (COP)', 'Descuento (COP)', 'Motivo']
            ));

            foreach ($movimientos as $movimiento) {
                $esCaja = $movimiento['tipo'] === 'Caja';
                echo $this->excelRow([
                    ['value' => $movimiento['fecha']->format('d/m/Y H:i')],
                    ['value' => $movimiento['tipo']],
                    ['value' => $movimiento['detalle']],
                    ['value' => $movimiento['referencia']],
                    ['value' => $movimiento['usuario'] ?? ''],
                    ['value' => $esCaja ? null : $movimiento['entrada'], 'type' => 'Number', 'style' => 'Number'],
                    ['value' => $esCaja ? null : $movimiento['salida'], 'type' => 'Number', 'style' => 'Number'],
                    ['value' => $esCaja ? $movimiento['entrada'] : null, 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $esCaja ? $movimiento['salida'] : null, 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $movimiento['valor'], 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $movimiento['iva'] ?? null, 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $movimiento['ganancia'] ?? null, 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $movimiento['descuento'] ?? null, 'type' => 'Number', 'style' => 'Money'],
                    ['value' => $movimiento['motivo']],
                ]);
            }

            $lastRow = $movimientos->count() + 5;
            echo '</Table><AutoFilter x:Range="R5C1:R'.$lastRow.'C14" xmlns="urn:schemas-microsoft-com:office:excel"/><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>5</SplitHorizontal><TopRowBottomPane>5</TopRowBottomPane><ActivePane>2</ActivePane><PageSetup><Layout x:Orientation="Landscape"/></PageSetup><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight></Print></WorksheetOptions></Worksheet>';
            echo '</Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportPdf(Request $request): View
    {
        ['desde' => $from, 'hasta' => $to, 'buscar' => $buscar] = $this->validatedFilters($request);
        $movimientos = $this->collectMovements($from, $to, $buscar);

        return view('movimientos.pdf', compact('movimientos', 'from', 'to'));
    }

    /**
     * @return array{desde: Carbon|null, hasta: Carbon|null, buscar: string}
     */
    private function validatedFilters(Request $request): array
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $buscar = trim($request->string('buscar')->toString());

        return compact('desde', 'hasta', 'buscar');
    }

    private function collectMovements(?Carbon $desde, ?Carbon $hasta, string $buscar): Collection
    {
        $inventario = InventoryMovement::with(['producto', 'user'])
            ->when($desde, fn ($query) => $query->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('created_at', '<=', $hasta))
            ->when($buscar, fn ($query) => $query->where(function ($inner) use ($buscar) {
                $inner->where('referencia', 'like', "%{$buscar}%")
                    ->orWhere('motivo', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('producto_nombre', 'like', "%{$buscar}%")
                    ->orWhere('producto_codigo', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', fn ($product) => $product->where('nombre', 'like', "%{$buscar}%"))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$buscar}%"));
            }))
            ->latest()->get()
            ->map(fn ($item) => [
                'fecha' => $item->created_at,
                'tipo' => 'Inventario',
                'detalle' => ($item->producto_nombre ?? $item->producto?->nombre)
                    .' · '.($item->producto_codigo ?? $item->producto?->codigo)
                    .' · '.$item->tipo,
                'referencia' => $item->referencia ?? 'AJ-'.$item->id,
                'usuario' => $item->user?->name,
                'entrada' => $item->cantidad > 0 ? $item->cantidad : null,
                'salida' => $item->cantidad < 0 ? abs($item->cantidad) : null,
                'valor' => $item->cantidad * $item->costo_unitario,
                'motivo' => $item->motivo,
            ]);

        $ventas = Venta::with(['user', 'detalles.producto'])
            ->when($desde, fn ($query) => $query->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('fecha', '<=', $hasta))
            ->when($buscar, fn ($query) => $query->where(function ($inner) use ($buscar) {
                $inner->where('comprobante', 'like', "%{$buscar}%")
                    ->orWhere('metodo_pago', 'like', "%{$buscar}%")
                    ->orWhereHas('detalles', fn ($details) => $details
                        ->where('producto_nombre', 'like', "%{$buscar}%")
                        ->orWhere('producto_codigo', 'like', "%{$buscar}%"))
                    ->orWhereHas('detalles.producto', fn ($product) => $product->where('nombre', 'like', "%{$buscar}%"))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$buscar}%"));
            }))
            ->latest('fecha')->get()
            ->map(fn ($item) => [
                'fecha' => $item->fecha,
                'tipo' => 'Venta',
                'detalle' => $item->detalles->count().' artículo(s)',
                'referencia' => $item->comprobante,
                'usuario' => $item->user?->name,
                'entrada' => null,
                'salida' => $item->detalles->sum('cantidad'),
                'valor' => $item->total,
                'iva' => $item->iva,
                'ganancia' => $item->ganancia,
                'descuento' => $item->descuento,
                'motivo' => 'Venta '.$item->metodo_pago,
            ]);

        $caja = CajaMovimiento::with('user')
            ->when($desde, fn ($query) => $query->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('fecha', '<=', $hasta))
            ->when($buscar, fn ($query) => $query->where(function ($inner) use ($buscar) {
                $inner->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$buscar}%"));
            }))
            ->latest('fecha')->get()
            ->map(fn ($item) => [
                'fecha' => $item->fecha,
                'tipo' => 'Caja',
                'detalle' => $item->descripcion,
                'referencia' => 'CAJA-'.$item->id,
                'usuario' => $item->user?->name,
                'entrada' => $item->tipo === 'transferencia_banco' ? null : $item->amount,
                'salida' => $item->tipo === 'transferencia_banco' ? $item->amount : null,
                'valor' => $item->amount,
                'motivo' => str_replace('_', ' ', $item->tipo),
            ]);

        return $inventario->concat($ventas)->concat($caja)->sortByDesc('fecha')->values();
    }

    /**
     * @param  array<int, array{value: string|int|float|null, type?: string, style?: string}>  $cells
     */
    private function excelRow(array $cells): string
    {
        $row = '<Row>';

        foreach ($cells as $cell) {
            $style = $cell['style'] ?? 'Body';

            if (($cell['value'] ?? null) === null) {
                $row .= '<Cell ss:StyleID="'.$style.'"/>';

                continue;
            }

            $value = htmlspecialchars((string) $cell['value'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $type = $cell['type'] ?? 'String';
            $row .= '<Cell ss:StyleID="'.$style.'"><Data ss:Type="'.$type.'">'.$value.'</Data></Cell>';
        }

        return $row.'</Row>';
    }
}
