<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Comision;
use App\Models\Reserva;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComisionController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/comisiones?semana=&anio=
    // Vista rápida por semana ISO (igual que antes).
    // ──────────────────────────────────────────────────────────────
    public function semana(Request $request)
    {
        $request->validate([
            'semana' => 'nullable|integer|min:1|max:53',
            'anio' => 'nullable|integer|min:2020',
        ]);

        $barbero = $this->barberoAutenticado($request);

        $semana = (int) ($request->input('semana') ?? now()->isoWeek());
        $anio = (int) ($request->input('anio') ?? now()->isoWeekYear());

        $inicioSemana = Carbon::now()->setISODate($anio, $semana, 1)->startOfDay();
        $finSemana = Carbon::now()->setISODate($anio, $semana, 7)->endOfDay();
        $corteConsolidacion = Carbon::now()->setISODate($anio, $semana, 7)->setTime(21, 0, 0);

        $reporte = $this->construirReporte($barbero, $inicioSemana, $finSemana, null);

        return response()->json([
            'modo' => 'semana',
            'semana' => $semana,
            'anio' => $anio,
            'fecha_inicio' => $inicioSemana->format('Y-m-d'),
            'fecha_fin' => $finSemana->format('Y-m-d'),
            'consolidado' => now()->gte($corteConsolidacion),
            ...$reporte,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/comisiones/filtrar?desde=&hasta=&cliente=
    // Rango libre (un solo día si desde === hasta) + filtro opcional
    // por nombre, apellido o CI del cliente.
    // ──────────────────────────────────────────────────────────────
    public function filtrar(Request $request)
    {
        $request->validate([
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
            'cliente' => 'nullable|string|max:50',
        ]);

        $barbero = $this->barberoAutenticado($request);

        $desde = Carbon::parse($request->desde)->startOfDay();
        $hasta = Carbon::parse($request->hasta)->endOfDay();

        $reporte = $this->construirReporte($barbero, $desde, $hasta, $request->input('cliente'));

        return response()->json([
            'modo' => 'personalizado',
            'fecha_inicio' => $desde->format('Y-m-d'),
            'fecha_fin' => $hasta->format('Y-m-d'),
            'cliente_filtro' => $request->input('cliente'),
            ...$reporte,
        ]);
    }

    // ── Helper: lógica común de agrupación en bloques + totales ──
    private function construirReporte(Barbero $barbero, Carbon $desde, Carbon $hasta, ?string $clienteBusqueda): array
    {
        $comisiones = Comision::where('IdBarbero', $barbero->IdBarbero)
            ->whereBetween('Fecha', [$desde, $hasta])
            ->get();

        $idsVenta = $comisiones->where('TipoComision', Comision::TIPO_PRODUCTO)->pluck('IdVenta')->filter()->unique();
        $ventas = Venta::whereIn('IdVenta', $idsVenta)->with('detalles.producto')->get()->keyBy('IdVenta');

        $idsReserva = $comisiones->pluck('IdReserva')->filter()->unique()
            ->merge($ventas->pluck('IdReserva')->filter())
            ->unique();
        $reservas = Reserva::whereIn('IdReserva', $idsReserva)
            ->with(['cliente', 'servicios'])
            ->get()
            ->keyBy('IdReserva');

        $bloques = [];

        foreach ($comisiones as $c) {
            if ($c->IdReserva) {
                $idReserva = $c->IdReserva;
            } elseif ($c->IdVenta && $ventas[$c->IdVenta]?->IdReserva) {
                $idReserva = $ventas[$c->IdVenta]->IdReserva;
            } else {
                $idReserva = null;
            }

            $clave = $idReserva ? "reserva-{$idReserva}" : ($c->IdVenta ? "venta-{$c->IdVenta}" : null);
            if (!$clave) continue;

            if (!isset($bloques[$clave])) {
                $bloques[$clave] = $idReserva
                    ? $this->iniciarBloqueCita($reservas[$idReserva] ?? null, $c->Fecha)
                    : $this->iniciarBloqueVentaDirecta($ventas[$c->IdVenta] ?? null, $c->Fecha);
            }

            $this->acumularEnBloque($bloques[$clave], $c, $ventas);
        }

        $bloques = array_values($bloques);

        // Filtro por cliente: nombre completo o CI, sobre los bloques ya armados.
        if ($clienteBusqueda) {
            $criterio = mb_strtolower(trim($clienteBusqueda));
            $bloques = array_values(array_filter($bloques, function ($b) use ($criterio) {
                $nombre = mb_strtolower($b['cliente'] ?? '');
                $ci = mb_strtolower($b['cliente_ci'] ?? '');
                return str_contains($nombre, $criterio) || str_contains($ci, $criterio);
            }));
        }

        usort($bloques, fn ($a, $b) => strcmp($a['fecha'], $b['fecha']));

        $totalServicios = array_sum(array_column($bloques, 'comision_servicio'));
        $totalProductos = array_sum(array_column($bloques, 'comision_producto'));
        $totalAusentes = array_sum(array_column($bloques, 'comision_ausente'));
        $totalNeto = $totalServicios + $totalProductos + $totalAusentes;

        return [
            'bloques' => $bloques,
            'total_servicios' => round($totalServicios, 2),
            'total_productos' => round($totalProductos, 2),
            'total_ausentes' => round($totalAusentes, 2),
            'total_neto' => round($totalNeto, 2),
        ];
    }

    private function iniciarBloqueCita(?Reserva $reserva, $fechaComision): array
    {
        return [
            'tipo' => 'cita',
            'id_referencia' => $reserva?->IdReserva,
            'fecha' => $fechaComision->format('Y-m-d'),
            'hora' => $reserva?->HoraInicio,
            'cliente' => $reserva
                ? trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? ''))
                : null,
            'cliente_ci' => $reserva->cliente->CI ?? null,
            'servicios' => $reserva ? $reserva->servicios->pluck('Nombre') : collect(),
            'productos' => [],
            'estado_cita' => $reserva?->EstadoReserva,
            'comision_servicio' => 0.0,
            'comision_producto' => 0.0,
            'comision_ausente' => 0.0,
            'comision_total' => 0.0,
        ];
    }

    private function iniciarBloqueVentaDirecta(?Venta $venta, $fechaComision): array
    {
        $cliente = $venta ? Cliente::where('CI', $venta->IdCliente)->first() : null;

        return [
            'tipo' => 'venta_directa',
            'id_referencia' => $venta?->IdVenta,
            'fecha' => $fechaComision->format('Y-m-d'),
            'hora' => null,
            'cliente' => $cliente ? trim($cliente->Nombre1 . ' ' . $cliente->Apellido1) : null,
            'cliente_ci' => $cliente?->CI,
            'servicios' => collect(),
            'productos' => $venta ? $venta->detalles->map(fn ($d) => [
                'nombre' => $d->producto?->Nombre,
                'cantidad' => $d->Cantidad,
            ]) : [],
            'estado_cita' => null,
            'comision_servicio' => 0.0,
            'comision_producto' => 0.0,
            'comision_ausente' => 0.0,
            'comision_total' => 0.0,
        ];
    }

    private function acumularEnBloque(array &$bloque, Comision $c, $ventas): void
    {
        $monto = (float) $c->MontoComision;

        match ($c->TipoComision) {
            Comision::TIPO_SERVICIO => $bloque['comision_servicio'] += $monto,
            Comision::TIPO_PRODUCTO => $bloque['comision_producto'] += $monto,
            Comision::TIPO_AUSENTE => $bloque['comision_ausente'] += $monto,
            default => null,
        };

        $bloque['comision_total'] += $monto;

        if ($c->TipoComision === Comision::TIPO_PRODUCTO && $c->IdVenta && $bloque['tipo'] === 'cita' && empty($bloque['productos'])) {
            $venta = $ventas[$c->IdVenta] ?? null;
            if ($venta) {
                $bloque['productos'] = $venta->detalles->map(fn ($d) => [
                    'nombre' => $d->producto?->Nombre,
                    'cantidad' => $d->Cantidad,
                ]);
            }
        }
    }

    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->with(['usuario' => fn ($q) => $q->select('IdUsuario', 'Nombre1', 'Apellido1')])
            ->firstOrFail();
    }
}