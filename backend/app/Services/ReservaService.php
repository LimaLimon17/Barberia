<?php

namespace App\Services;

use App\Models\Barbero;
use App\Models\Horario;
use App\Models\HorarioSemanal;
use App\Models\Reserva;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaService
{
    public const MINUTOS_LIMPIEZA = 10;
    public const MINUTOS_TOLERANCIA = 5;
    public const HORA_APERTURA = '10:00';
    public const HORA_CIERRE = '22:00';
    public const PASO_SLOTS_MINUTOS = 15;
    public const PORCENTAJE_ANTICIPO = 0.5;
    public const MINUTOS_EXPIRACION_PAGO = 15;

    // Estados posibles de Reservas.EstadoReserva
    public const PENDIENTE = 'Pendiente';
    public const CONFIRMADA = 'Confirmada';
    public const CANCELADA = 'Cancelada';
    public const EXPIRADA = 'Expirada';
    public const INVALIDADA = 'Invalidada'; // perdió el slot ante otro cliente

    public function __construct(private AlmuerzoService $almuerzoService)
    {
    }

    /**
     * Duración total del bloque (Escenario 4 y 5 de HU-04):
     * suma de duraciones + 10 min limpieza + 5 min tolerancia.
     */
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
     * Genera los slots de horaInicio candidatos del día (cada 15 min) que
     * caben dentro del bloque [10:00, 22:00], excluyendo almuerzo/descanso
     * y conflictos con otras reservas vigentes del barbero.
     *
     * Devuelve: [['hora_inicio' => 'HH:mm', 'hora_fin' => 'HH:mm', 'disponible' => bool], ...]
     */
    public function obtenerSlotsDisponibles(Barbero $barbero, Carbon $fecha, int $duracionTotalMinutos): array
    {
        $diaSemana = AlmuerzoService::nombreDiaEs($fecha);
        $semana = (int) $fecha->isoWeek();
        $anio = (int) $fecha->isoWeekYear();

        $horarioSemanal = HorarioSemanal::where('IdBarbero', $barbero->IdBarbero)
            ->where('Semana', $semana)->where('Año', $anio)->first();

        if (!$horarioSemanal) {
            return [];
        }

        $horarioDia = Horario::where('IdHorarioSemanal', $horarioSemanal->IdHorarioSemanal)
            ->where('DiaSemana', $diaSemana)
            ->first();

        // Sin horario configurado o es su día de descanso semanal: no trabaja.
        if (!$horarioDia || $horarioDia->DiaDescanso) {
            return [];
        }

        [$almuerzoInicio, $almuerzoFin] = $this->almuerzoService->bloqueAlmuerzo($barbero, $fecha);

        $aperturaOperativa = Carbon::parse($fecha->format('Y-m-d') . ' ' . self::HORA_APERTURA);
        $cierreOperativo = Carbon::parse($fecha->format('Y-m-d') . ' ' . self::HORA_CIERRE);

        $entradaBarbero = Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioDia->HoraEntrada);
        $salidaBarbero = Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioDia->HoraSalida);

        $inicioVentana = $aperturaOperativa->max($entradaBarbero);
        $finVentana = $cierreOperativo->min($salidaBarbero);

        $almuerzoInicioDt = Carbon::parse($fecha->format('Y-m-d') . ' ' . $almuerzoInicio);
        $almuerzoFinDt = Carbon::parse($fecha->format('Y-m-d') . ' ' . $almuerzoFin);

        $reservasVigentes = $this->reservasVigentesDelDia($barbero->IdBarbero, $fecha);

        $slots = [];
        $cursor = $inicioVentana->copy();

        while (true) {
            $finBloque = $cursor->copy()->addMinutes($duracionTotalMinutos);

            if ($finBloque->gt($finVentana)) {
                break;
            }

            $disponible = true;

            // No puede superar el cierre operativo (10pm).
            if ($finBloque->gt($cierreOperativo)) {
                $disponible = false;
            }

            // No puede solaparse con el almuerzo/descanso.
            if ($disponible && $this->seSolapan($cursor, $finBloque, $almuerzoInicioDt, $almuerzoFinDt)) {
                $disponible = false;
            }

            // No puede solaparse con otra reserva vigente del mismo barbero.
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

    /**
     * Reservas que aún bloquean la agenda del barbero ese día:
     * Confirmadas, o Pendientes que todavía no expiraron (< 15 min de antigüedad).
     */
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

    /**
     * Estado de disponibilidad "ahora" de un barbero, para el grid de RF3.
     */
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

    /**
     * Valida y crea la reserva en estado Pendiente (Escenario 2 y 6 de HU-04).
     * No bloquea contra otras reservas Pendientes (solo contra Confirmadas y
     * Pendientes no expiradas) para permitir el flujo de "primero en pagar"
     * descrito en HU-05 Escenario 5.
     */
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

    /**
     * Confirma el pago del anticipo (HU-05 Escenario 2) y, en la misma
     * transacción, invalida cualquier otra reserva Pendiente que compita
     * por el mismo barbero/horario (HU-05 Escenario 5).
     */
    public function confirmarPago(Reserva $reserva, string $metodoPago): Reserva
    {
        return DB::transaction(function () use ($reserva, $metodoPago) {
            /** @var Reserva $reservaLock */
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

            // Re-validar que ninguna OTRA reserva ya esté Confirmada para ese
            // mismo barbero/horario (alguien pagó primero).
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
                'MetodoPagoFinal' => $metodoPago,
            ]);

            // Invalidar otras reservas Pendientes que compitan por el mismo
            // bloque (Escenario 5 de HU-05): el Cliente A pierde el cupo.
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

    /**
     * Expira una reserva si superó los 15 minutos sin pago (HU-05 Escenario 3).
     * Pensado para ser llamado desde el Job programado al crear la reserva.
     */
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
