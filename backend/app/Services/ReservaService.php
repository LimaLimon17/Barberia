<?php

namespace App\Services;

use App\Models\Barbero;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Pago;


class ReservaService
{
    public const MINUTOS_LIMPIEZA = 10;
    public const MINUTOS_TOLERANCIA = 5;
    public const HORA_APERTURA = '10:00';
    public const HORA_CIERRE = '22:00';
    public const PASO_SLOTS_MINUTOS = 15;
    public const PORCENTAJE_ANTICIPO = 0.5;
    public const MINUTOS_EXPIRACION_PAGO = 15;

    public const PENDIENTE = 'Pendiente';
    public const CONFIRMADA = 'Confirmada';
    public const CANCELADA = 'Cancelada';
    public const EXPIRADA = 'Expirada';
    public const INVALIDADA = 'Invalidada';

    private const DIAS_ES = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo',
    ];

    /**
     * NOTA: ya no depende de AlmuerzoService. La restricción de almuerzo
     * fue eliminada del negocio; esos horarios ahora se pueden reservar
     * libremente si no hay otra cita en ese bloque.
     */
    public static function nombreDiaEs(Carbon $fecha): string
    {
        return self::DIAS_ES[$fecha->format('l')];
    }

    public function calcularDuracionTotal(Collection $servicios): int
    {
        $sumaServicios = (int) $servicios->sum('DuracionMinutos');
        return $sumaServicios + self::MINUTOS_LIMPIEZA + self::MINUTOS_TOLERANCIA;
    }

    public function calcularCostoTotal(Collection $servicios): float
    {
        return (float) $servicios->sum('Precio');
    }

    /**
     * Horario vigente del barbero para esa fecha exacta: busca en
     * HorariosBarberos el rango activo (FechaInicio..FechaFin) que
     * contenga la fecha, y dentro de ese rango el Horario (plantilla)
     * configurado para el día de la semana correspondiente.
     *
     * Si el barbero tuviera más de una asignación vigente para el mismo
     * día (caso poco común), se toma la primera encontrada.
     */
    // Cambiar de 'private' a 'public':
public function horarioVigenteDelDia(Barbero $barbero, Carbon $fecha): ?Horario
{
    $diaSemana = self::nombreDiaEs($fecha);

    return Horario::where('DiaSemana', $diaSemana)
        ->where('EstadoA', 1)
        ->whereHas('horariosBarberos', function ($q) use ($barbero, $fecha) {
            $q->where('IdBarbero', $barbero->IdBarbero)
              ->where('EstadoA', 1)
              ->where('FechaInicio', '<=', $fecha->format('Y-m-d'))
              ->where('FechaFin', '>=', $fecha->format('Y-m-d'));
        })
        ->first();
}

/**
 * Estado del barbero EN ESTE INSTANTE, para el grid de disponibilidad:
 * 'fuera_de_horario'  -> antes de las 10:00 o después de las 22:00 (cierre operativo)
 * 'descanso'          -> hoy es su día de descanso, o no tiene horario asignado
 * 'ocupado'           -> tiene una reserva Confirmada que cubre este instante
 * 'disponible'        -> cualquier otro caso
 */
public function estadoBarberoAhora(Barbero $barbero): string
{
    $ahora = Carbon::now();

    if ($ahora->format('H:i:s') < self::HORA_APERTURA . ':00' || $ahora->format('H:i:s') >= self::HORA_CIERRE . ':00') {
        return 'fuera_de_horario';
    }

    $horarioDia = $this->horarioVigenteDelDia($barbero, $ahora);

    if (!$horarioDia || $horarioDia->DiaDescanso) {
        return 'descanso';
    }

    // Fuera de su horario personal de entrada/salida ese día (aunque la barbería esté abierta)
    if ($ahora->format('H:i:s') < $horarioDia->HoraEntrada || $ahora->format('H:i:s') >= $horarioDia->HoraSalida) {
        return 'descanso';
    }

    if ($this->estaOcupadoAhora($barbero)) {
        return 'ocupado';
    }

    return 'disponible';
}

    /**
     * Genera los slots de horaInicio candidatos del día (cada 15 min) que
     * caben dentro del bloque [10:00, 22:00], excluyendo solo conflictos
     * con otras reservas vigentes del barbero. Ya NO hay bloqueo de
     * almuerzo/descanso adicional: ese tramo es reservable si está libre.
     */
    public function obtenerSlotsDisponibles(Barbero $barbero, Carbon $fecha, int $duracionTotalMinutos): array
    {
        $horarioDia = $this->horarioVigenteDelDia($barbero, $fecha);

        // Sin horario asignado ese día, o es su día de descanso: no trabaja.
        if (!$horarioDia || $horarioDia->DiaDescanso) {
            return [];
        }

        $aperturaOperativa = Carbon::parse($fecha->format('Y-m-d') . ' ' . self::HORA_APERTURA);
        $cierreOperativo = Carbon::parse($fecha->format('Y-m-d') . ' ' . self::HORA_CIERRE);

        $entradaBarbero = Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioDia->HoraEntrada);
        $salidaBarbero = Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioDia->HoraSalida);

        $inicioVentana = $aperturaOperativa->max($entradaBarbero);
        $finVentana = $cierreOperativo->min($salidaBarbero);

        $reservasVigentes = $this->reservasVigentesDelDia($barbero->IdBarbero, $fecha);

        $slots = [];
        $cursor = $inicioVentana->copy();

        while (true) {
            $finBloque = $cursor->copy()->addMinutes($duracionTotalMinutos);

            if ($finBloque->gt($finVentana)) {
                break;
            }

            $disponible = true;

            if ($finBloque->gt($cierreOperativo)) {
                $disponible = false;
            }

            if ($disponible) {
                foreach ($reservasVigentes as $r) {
                    $ocupadoInicio = Carbon::parse($fecha->format('Y-m-d') . ' ' . $r->HoraInicio);
                    $ocupadoFin = Carbon::parse($fecha->format('Y-m-d') . ' ' . $r->HoraFin);
                    if ($this->seSolapan($cursor, $finBloque, $ocupadoInicio, $ocupadoFin)) {
                        $disponible = false;
                        break;
                    }
                }
            }

            $slots[] = [
                'hora_inicio' => $cursor->format('H:i'),
                'hora_fin' => $finBloque->format('H:i'),
                'disponible' => $disponible,
            ];

            $cursor->addMinutes(self::PASO_SLOTS_MINUTOS);
        }

        return $slots;
    }

    private function seSolapan(Carbon $inicioA, Carbon $finA, Carbon $inicioB, Carbon $finB): bool
    {
        return $inicioA->lt($finB) && $finA->gt($inicioB);
    }

    private function reservasVigentesDelDia(int $idBarbero, Carbon $fecha): Collection
    {
        return Reserva::where('IdBarbero', $idBarbero)
            ->where('FechaCita', $fecha->format('Y-m-d'))
            ->where(function ($q) {
                $q->where('EstadoReserva', self::CONFIRMADA)
                    ->orWhere(function ($q2) {
                        $q2->where('EstadoReserva', self::PENDIENTE)
                            ->where('FechaA', '>=', now()->subMinutes(self::MINUTOS_EXPIRACION_PAGO));
                    });
            })
            ->get();
    }

    public function estaOcupadoAhora(Barbero $barbero): bool
    {
        $ahora = Carbon::now();
        return Reserva::where('IdBarbero', $barbero->IdBarbero)
            ->where('FechaCita', $ahora->format('Y-m-d'))
            ->where('EstadoReserva', self::CONFIRMADA)
            ->where('HoraInicio', '<=', $ahora->format('H:i:s'))
            ->where('HoraFin', '>', $ahora->format('H:i:s'))
            ->exists();
    }

    public function crearReservaPendiente(array $datosCliente, Barbero $barbero, Collection $servicios, Carbon $fecha, string $horaInicio): Reserva
    {
        $duracionTotal = $this->calcularDuracionTotal($servicios);
        $costoTotal = $this->calcularCostoTotal($servicios);

        $inicio = Carbon::parse($fecha->format('Y-m-d') . ' ' . $horaInicio);
        $fin = $inicio->copy()->addMinutes($duracionTotal);

        $slotsDelDia = $this->obtenerSlotsDisponibles($barbero, $fecha, $duracionTotal);
        $slotValido = collect($slotsDelDia)->firstWhere('hora_inicio', $inicio->format('H:i'));

        if (!$slotValido || !$slotValido['disponible']) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El horario seleccionado ya no está disponible. Por favor elige otro horario.',
            ]);
        }

        return DB::transaction(function () use ($datosCliente, $barbero, $servicios, $fecha, $inicio, $fin, $costoTotal) {
            $reserva = Reserva::create([
                'IdCliente' => $datosCliente['CI'],
                'IdBarbero' => $barbero->IdBarbero,
                'FechaCita' => $fecha->format('Y-m-d'),
                'HoraInicio' => $inicio->format('H:i:s'),
                'HoraFin' => $fin->format('H:i:s'),
                'CostoTotal' => $costoTotal,
                'MontoAnticipo' => round($costoTotal * self::PORCENTAJE_ANTICIPO, 2),
                'EstadoReserva' => self::PENDIENTE,
                'EstadoA' => 1,
                'FechaA' => now(),
                'UsuarioA' => 1,
            ]);

            foreach ($servicios as $servicio) {
                $reserva->servicios()->attach($servicio->IdServicio, [
                    'EstadoA' => 1,
                    'FechaA' => now(),
                    'UsuarioA' => 1,
                ]);
            }

            return $reserva;
        });
    }


public function confirmarPago(Reserva $reserva, string $metodoPago): Reserva
{
    return DB::transaction(function () use ($reserva, $metodoPago) {
        $reservaLock = Reserva::where('IdReserva', $reserva->IdReserva)->lockForUpdate()->first();

        if ($reservaLock->EstadoReserva !== self::PENDIENTE) {
            throw ValidationException::withMessages([
                'estado' => 'Esta reserva ya no está pendiente de pago (Tiempo Expirado o ya fue procesada).',
            ]);
        }

        $minutosTranscurridos = Carbon::parse($reservaLock->FechaA)->diffInMinutes(now());
        if ($minutosTranscurridos > self::MINUTOS_EXPIRACION_PAGO) {
            $reservaLock->update(['EstadoReserva' => self::EXPIRADA]);
            throw ValidationException::withMessages([
                'estado' => 'Tiempo Expirado: superó los 15 minutos para pagar el anticipo.',
            ]);
        }

        $conflictoConfirmado = Reserva::where('IdBarbero', $reservaLock->IdBarbero)
            ->where('FechaCita', $reservaLock->FechaCita)
            ->where('IdReserva', '<>', $reservaLock->IdReserva)
            ->where('EstadoReserva', self::CONFIRMADA)
            ->where('HoraInicio', '<', $reservaLock->HoraFin)
            ->where('HoraFin', '>', $reservaLock->HoraInicio)
            ->exists();

        if ($conflictoConfirmado) {
            $reservaLock->update(['EstadoReserva' => self::INVALIDADA]);
            throw ValidationException::withMessages([
                'estado' => 'El horario seleccionado ya no se encuentra disponible.',
            ]);
        }

        $reservaLock->update([
            'EstadoReserva' => self::CONFIRMADA,
            'FechaPagoAnticipo' => now(),
            'MetodoPagoAnticipo' => $metodoPago,
        ]);

        // Registro formal del pago (Anticipo = 50%, distinto del Total presencial)
        Pago::create([
            'IdReserva'  => $reservaLock->IdReserva,
            'IdVenta'    => null,
            'TipoPago'   => 'Anticipo',
            'Monto'      => $reservaLock->MontoAnticipo,
            'FechaPago'  => now(),
            'MetodoPago' => $metodoPago,
            'EstadoPago' => 'Pagado',
            'EstadoA'    => 1,
            'FechaA'     => now(),
            'UsuarioA'   => 1, // flujo público: sin usuario autenticado, igual que el resto del archivo
        ]);

        Reserva::where('IdBarbero', $reservaLock->IdBarbero)
            ->where('FechaCita', $reservaLock->FechaCita)
            ->where('IdReserva', '<>', $reservaLock->IdReserva)
            ->where('EstadoReserva', self::PENDIENTE)
            ->where('HoraInicio', '<', $reservaLock->HoraFin)
            ->where('HoraFin', '>', $reservaLock->HoraInicio)
            ->update(['EstadoReserva' => self::INVALIDADA]);

        return $reservaLock->fresh();
    });
}

    public function expirarSiCorresponde(Reserva $reserva): void
    {
        if ($reserva->EstadoReserva !== self::PENDIENTE) {
            return;
        }

        $minutosTranscurridos = Carbon::parse($reserva->FechaA)->diffInMinutes(now());
        if ($minutosTranscurridos >= self::MINUTOS_EXPIRACION_PAGO) {
            $reserva->update(['EstadoReserva' => self::EXPIRADA]);
        }
    }
 
}