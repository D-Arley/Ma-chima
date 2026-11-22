<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_report_uses_monday_to_sunday_calendar_weeks_and_month_ranges(): void
    {
        $this->travelTo('2026-11-22 12:00:00');
        $seller = User::factory()->create(['role' => 'vendedor']);
        Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $this->createSale($seller, 'VEN-REPORT-001', '2026-11-01 10:00:00', 30000, 7500);
        $this->createSale($seller, 'VEN-REPORT-002', '2026-11-02 10:00:00', 40000, 10000);
        $this->createSale($seller, 'VEN-REPORT-003', '2026-11-15 10:00:00', 70000, 17500);
        $this->createSale($seller, 'VEN-REPORT-004', '2026-11-16 10:00:00', 20000, 5000);
        $this->createSale($seller, 'VEN-REPORT-005', '2026-11-22 10:00:00', 80000, 20000);
        $this->createSale($seller, 'VEN-REPORT-006', '2026-11-30 10:00:00', 90000, 22500);

        $response = $this->actingAs($seller)->get(route('caja.index'));

        $response->assertOk()
            ->assertSee('Semana calendario · lunes a domingo')
            ->assertSee('01/11 - 01/11')
            ->assertSee('02/11 - 08/11')
            ->assertSee('30/11 - 30/11');

        $semanal = $response->viewData('semanal');
        $this->assertCount(7, $semanal);
        $this->assertSame('16/11', $semanal->first()['fecha']);
        $this->assertSame('22/11', $semanal->last()['fecha']);
        $this->assertSame(100000.0, $response->viewData('totalSemana'));
        $this->assertSame(25000.0, $response->viewData('gananciaSemana'));

        $semanasMes = $response->viewData('semanasMes');
        $this->assertSame([
            '01/11 - 01/11',
            '02/11 - 08/11',
            '09/11 - 15/11',
            '16/11 - 22/11',
            '23/11 - 29/11',
            '30/11 - 30/11',
        ], $semanasMes->pluck('rango')->all());
        $this->assertSame(100000.0, $semanasMes[3]['ventas']);
        $this->assertSame(25000.0, $semanasMes[3]['ganancia']);
    }

    public function test_weekly_report_remains_visible_before_opening_the_daily_cashbox(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);

        $this->actingAs($seller)
            ->get(route('caja.index'))
            ->assertOk()
            ->assertSee('Abrir caja del día')
            ->assertSee('Semana calendario · lunes a domingo')
            ->assertSee('Desglose del mes · ventas y ganancia neta');
    }

    private function createSale(User $seller, string $receipt, string $date, int $total, int $profit): void
    {
        Venta::create([
            'user_id' => $seller->id,
            'comprobante' => $receipt,
            'subtotal' => $total,
            'descuento' => 0,
            'iva' => 0,
            'ganancia' => $profit,
            'total' => $total,
            'metodo_pago' => 'efectivo',
            'efectivo_recibido' => $total,
            'cambio' => 0,
            'fecha' => $date,
        ]);
    }
}
