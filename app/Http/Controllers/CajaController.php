<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function index(): View
    {
        $caja = Caja::with(['movimientos' => fn ($query) => $query->latest('fecha')->limit(30)])
            ->where('user_id', auth()->id())
            ->whereDate('fecha', today())
            ->first();

        $ventasHoy = $caja?->ventas()->with('detalles.producto')->latest('fecha')->get() ?? collect();

        $efectivoHoy = $ventasHoy->where('metodo_pago', 'efectivo')->sum('total');
        $transferenciasHoy = $ventasHoy->where('metodo_pago', 'transferencia')->sum('total');

        $inicioSemana = today()->startOfWeek(Carbon::MONDAY);
        $finSemana = $inicioSemana->copy()->addDays(6)->endOfDay();
        $ventasPorDia = Venta::whereBetween('fecha', [$inicioSemana->copy()->startOfDay(), $finSemana])
            ->selectRaw('DATE(fecha) as dia, SUM(total) as ventas, SUM(ganancia) as ganancia')
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        $semanal = collect();
        for ($day = 0; $day < 7; $day++) {
            $fecha = $inicioSemana->copy()->addDays($day);
            $ventasDelDia = $ventasPorDia->get($fecha->toDateString());
            $semanal->push([
                'dia' => $fecha->translatedFormat('D'),
                'fecha' => $fecha->format('d/m'),
                'ventas' => (float) ($ventasDelDia->ventas ?? 0),
                'ganancia' => (float) ($ventasDelDia->ganancia ?? 0),
            ]);
        }

        $totalSemana = $semanal->sum('ventas');
        $gananciaSemana = $semanal->sum('ganancia');

        $semanasMes = collect();
        $inicioMes = today()->startOfMonth();
        $finMes = today()->endOfMonth();
        $ventasDelMes = Venta::whereBetween('fecha', [$inicioMes->copy()->startOfDay(), $finMes->copy()->endOfDay()])
            ->selectRaw('DATE(fecha) as dia, SUM(total) as ventas, SUM(ganancia) as ganancia')
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');
        $inicioSemanaMes = $inicioMes->copy();
        $indice = 1;
        while ($inicioSemanaMes <= $finMes) {
            $finSemanaMes = $inicioSemanaMes->copy()
                ->endOfWeek(Carbon::SUNDAY)
                ->min($finMes);
            $ventasSemanaMes = 0;
            $gananciaSemanaMes = 0;
            $fechaSemana = $inicioSemanaMes->copy();

            while ($fechaSemana <= $finSemanaMes) {
                $ventasDelDia = $ventasDelMes->get($fechaSemana->toDateString());
                $ventasSemanaMes += (float) ($ventasDelDia->ventas ?? 0);
                $gananciaSemanaMes += (float) ($ventasDelDia->ganancia ?? 0);
                $fechaSemana->addDay();
            }

            $semanasMes->push([
                'semana' => 'Semana '.$indice,
                'rango' => $inicioSemanaMes->format('d/m').' - '.$finSemanaMes->format('d/m'),
                'ventas' => $ventasSemanaMes,
                'ganancia' => $gananciaSemanaMes,
            ]);
            $inicioSemanaMes = $finSemanaMes->copy()->addDay();
            $indice++;
        }

        return view('caja.index', compact(
            'caja',
            'ventasHoy',
            'efectivoHoy',
            'transferenciasHoy',
            'semanal',
            'totalSemana',
            'gananciaSemana',
            'semanasMes'
        ));
    }

    public function open(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:200000'],
        ]);

        $opening = round((float) $validated['opening_cash'], 2);
        DB::transaction(function () use ($opening) {
            User::query()->lockForUpdate()->findOrFail(auth()->id());

            if (Caja::where('user_id', auth()->id())->whereDate('fecha', today())->exists()) {
                throw ValidationException::withMessages(['opening_cash' => 'La caja de hoy ya fue abierta.']);
            }

            $caja = Caja::create([
                'user_id' => auth()->id(),
                'fecha' => today(),
                'opening_cash' => $opening,
                'cash_balance' => $opening,
                'transfer_balance' => 0,
                'bank_balance' => 0,
                'status' => 'abierta',
                'opened_at' => now(),
            ]);

            CajaMovimiento::create([
                'caja_id' => $caja->id,
                'user_id' => auth()->id(),
                'tipo' => 'apertura',
                'amount' => $opening,
                'cash_after' => $opening,
                'bank_after' => 0,
                'descripcion' => 'Apertura de caja para la jornada.',
                'fecha' => now(),
            ]);
        });

        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        DB::transaction(function () use ($amount) {
            $caja = Caja::where('user_id', auth()->id())
                ->whereDate('fecha', today())
                ->where('status', 'abierta')
                ->lockForUpdate()
                ->first();

            if (! $caja) {
                throw ValidationException::withMessages(['amount' => 'Debe abrir la caja antes de realizar una transferencia.']);
            }

            $maximum = max(0, (float) $caja->cash_balance - 200000);

            if ($amount > $maximum) {
                throw ValidationException::withMessages([
                    'amount' => 'No puede transferir más de '.number_format($maximum, 0, ',', '.').' COP. Siempre debe quedar un mínimo de 200.000 COP en caja.',
                ]);
            }

            $caja->update([
                'cash_balance' => (float) $caja->cash_balance - $amount,
                'bank_balance' => (float) $caja->bank_balance + $amount,
            ]);

            CajaMovimiento::create([
                'caja_id' => $caja->id,
                'user_id' => auth()->id(),
                'tipo' => 'transferencia_banco',
                'amount' => $amount,
                'cash_after' => $caja->cash_balance,
                'bank_after' => $caja->bank_balance,
                'descripcion' => 'Traslado manual de efectivo físico a cuenta bancaria.',
                'fecha' => now(),
            ]);
        });

        return back()->with('success', 'Dinero trasladado a la cuenta bancaria.');
    }
}
