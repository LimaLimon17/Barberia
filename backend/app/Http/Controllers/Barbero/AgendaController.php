<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Comision;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * AgendaController
 * ─────────────────────────────────────────────────────────────────
 * Fase 1: Agenda del barbero + cambio de estado de citas.
 * RF5  - Búsqueda de citas por nombre, CI o teléfono.
 * RF9  - Marcar Ausente si el cliente no llega 5 min después del inicio.
 * RF10 - Retención del 50% del anticipo al marcar Ausente, repartido
 *        en partes iguales entre fondos de la barbería y comisión del barbero.
 * HU-06 - Gestión de citas por el barbero.
 */
class AgendaController extends Controller
{
    private const MINUTOS_TOLERANCIA_AUSENTE = 5;

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/agenda/hoy
    // Citas del día ordenadas cronológicamente.
    // ──────────────────────────────────────────────────────────────
    public function citasHoy(Request $request)
    {
        $barbero = $this->barberoAutenticado($request);
        $hoy = now()->format('Y-m-d');

        $citas = Reserva::where('IdBarbero', $barbero->IdBarbero)
            ->where('FechaCita', $hoy)
            ->whereIn('EstadoReserva', ['Confirmada', 'Completada', 'Ausente'])
            ->with(['cliente', 'servicios'])
            ->orderBy('HoraInicio')
            ->get()
            ->map(fn ($r) => $this->mapearCita($r));

        return response()->json(['citas' => $citas]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/agenda/buscar?criterio=
    // Busca citas futuras del barbero por nombre, teléfono o CI del cliente.
    // Ordenadas por proximidad (más cercanas primero).
    // ──────────────────────────────────────────────────────────────
    public function buscarCitas(Request $request)
    {
        $request->validate([
            'criterio' => 'required|string|min:2|max:50',
        ]);

        $barbero = $this->barberoAutenticado($request);
        $criterio = $request->input('criterio');
        $hoy = now()->format('Y-m-d');

        $citas = Reserva::where('IdBarbero', $barbero->IdBarbero)
            ->where('FechaCita', '>=', $hoy)
            ->whereIn('EstadoReserva', ['Confirmada', 'Completada', 'Ausente'])
            ->whereHas('cliente', function ($q) use ($criterio) {
                $q->where('CI', 'like', "%{$criterio}%")
                  ->orWhere('Telefono', 'like', "%{$criterio}%")
                  ->orWhereRaw("CONCAT(Nombre1, ' ', Apellido1) LIKE ?", ["%{$criterio}%"]);
            })
            ->with(['cliente', 'servicios'])
            ->orderBy('FechaCita')
            ->orderBy('HoraInicio')
            ->limit(50)
            ->get()
            ->map(fn ($r) => $this->mapearCita($r));

        return response()->json(['citas' => $citas]);
    }

    // ──────────────────────────────────────────────────────────────
    // PUT /api/barbero/citas/{idReserva}/estado
    // body: { estado: 'Ausente' | 'Completada' }
    // ──────────────────────────────────────────────────────────────
    public function cambiarEstado(Request $request, int $idReserva)
    {
        $request->validate([
            'estado' => 'required|in:Ausente,Completada',
        ]);

        $barbero = $this->barberoAutenticado($request);
        DB::statement("SET @v_auditoria_ip = ?", [$request->ip()]);
        $idUsuarioAutenticado = $request->user()->IdUsuario;

        try {
            $reserva = DB::transaction(function () use ($barbero, $idReserva, $request, $idUsuarioAutenticado) {
                $reservaLock = Reserva::where('IdReserva', $idReserva)
                    ->where('IdBarbero', $barbero->IdBarbero)
                    ->lockForUpdate()
                    ->first();

                if (!$reservaLock) {
                    throw ValidationException::withMessages([
                        'id_reserva' => 'La cita no existe o no pertenece a este barbero.',
                    ]);
                }

                if ($reservaLock->EstadoReserva !== 'Confirmada') {
                    throw ValidationException::withMessages([
                        'estado' => 'Solo se puede cambiar el estado de una cita Confirmada.',
                    ]);
                }

                if ($request->estado === 'Ausente') {
                    $this->validarTiempoAusente($reservaLock);

                    $reservaLock->update([
                        'EstadoReserva' => 'Ausente',
                        'HoraAusente' => now(),
                    ]);

                    // RF10: retiene el 50% del anticipo; la mitad (25% del
                    // anticipo total) va a la comisión semanal del barbero.
                    // El otro 25% queda implícito como fondo de la barbería
                    // (no se modela como movimiento aparte; se calcula en
                    // los reportes financieros como complemento).
                    $montoComisionBarbero = round((float) $reservaLock->MontoAnticipo * 0.5, 2);

                    Comision::create([
                        'IdBarbero' => $barbero->IdBarbero,
                        'IdReserva' => $reservaLock->IdReserva,
                        'IdVenta' => null,
                        'TipoComision' => Comision::TIPO_AUSENTE,
                        'Fecha' => now(),
                        'MontoBase' => $reservaLock->MontoAnticipo,
                        'Porcentaje' => 50.00,
                        'MontoComision' => $montoComisionBarbero,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => $idUsuarioAutenticado,
                    ]);
                } else {
                    // 'Completada': solo el cambio de estado. El registro del
                    // pago final, productos y nota de venta se resuelven en
                    // la Fase 3 (pago final) y Fase 2 (venta de productos).
                    $reservaLock->update(['EstadoReserva' => 'Completada']);
                }

                return $reservaLock->fresh(['cliente', 'servicios']);
            });
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['cita' => $this->mapearCita($reserva)]);
    }

    // ── Helper: valida que hayan pasado al menos 5 min desde HoraInicio ──
    private function validarTiempoAusente(Reserva $reserva): void
    {
        $inicioReal = Carbon::parse($reserva->FechaCita->format('Y-m-d') . ' ' . $reserva->HoraInicio);
        $umbral = $inicioReal->copy()->addMinutes(self::MINUTOS_TOLERANCIA_AUSENTE);

        if (now()->lt($umbral)) {
            throw ValidationException::withMessages([
                'estado' => 'Aún no pasaron los 5 minutos de tolerancia desde la hora de inicio de la cita.',
            ]);
        }
    }

    // ── Helper: estructura de respuesta común para una cita ──
    private function mapearCita(Reserva $reserva): array
{
    return [
        'id_reserva'   => $reserva->IdReserva,
        'fecha'        => $reserva->FechaCita instanceof Carbon
            ? $reserva->FechaCita->format('Y-m-d')
            : $reserva->FechaCita,
        'hora_inicio'  => $reserva->HoraInicio,
        'hora_fin'     => $reserva->HoraFin,
        'estado'       => $reserva->EstadoReserva,
        'costo_total'  => (float) $reserva->CostoTotal,
        'monto_anticipo' => (float) $reserva->MontoAnticipo,
        // true si se cobró el 100%: cita presencial del mismo día
        'pago_completo' => (float) $reserva->MontoAnticipo >= (float) $reserva->CostoTotal,
        'cliente'      => [
            'ci'      => $reserva->cliente->CI ?? null,
            'nombre'  => trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? '')),
            'telefono' => $reserva->cliente->Telefono ?? null,
            'correo'  => $reserva->cliente->Correo ?? null,
        ],
        'servicios'    => $reserva->servicios->map(fn ($s) => [
            'nombre'   => $s->Nombre,
            'duracion' => $s->DuracionMinutos,
            'precio'   => (float) $s->Precio,
        ]),
    ];
}

    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->with(['usuario' => fn ($q) => $q->select('IdUsuario', 'Nombre1', 'Apellido1')])
            ->firstOrFail();
    }
}