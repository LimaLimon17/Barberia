<?php

namespace App\Services;

use App\Models\Barbero;
use App\Models\Horario;
use App\Models\HorarioSemanal;
use App\Models\TurnoAlmuerzoTardio;
use Carbon\Carbon;

/**
 * Reglas de negocio del almuerzo (RF de bloqueo automático 12:00-13:00):
 *
 * - Todos los barberos descansan 12:00-13:00, de lunes a domingo.
 * - Excepción: un barbero "tardío" elegido aleatoriamente al inicio de cada
 *   semana (sin repetir el de la semana anterior) descansa 13:00-14:00 toda
 *   esa semana.
 * - Si el día evaluado coincide con el día de descanso semanal de ese
 *   barbero tardío, el almuerzo tardío se transfiere, SOLO ese día, a un
 *   barbero sustituto definido en TurnoAlmuerzosTardios.
 * - Ningún cliente puede reservar dentro de estos rangos.
 */
class AlmuerzoService
{
    private const DIAS_ES = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo',
    ];

    public static function nombreDiaEs(Carbon $fecha): string
    {
        return self::DIAS_ES[$fecha->format('l')];
    }

    /**
     * Devuelve [horaInicio, horaFin] (strings H:i) del bloque de almuerzo
     * que aplica al barbero en esa fecha específica.
     */
    public function bloqueAlmuerzo(Barbero $barbero, Carbon $fecha): array
    {
        $semana = (int) $fecha->isoWeek();
        $anio = (int) $fecha->isoWeekYear();
        $diaSemana = self::nombreDiaEs($fecha);

        $turno = TurnoAlmuerzoTardio::where('Semana', $semana)
            ->where('Año', $anio)
            ->where('EstadoA', 1)
            ->first();

        if (!$turno) {
            return ['12:00', '13:00'];
        }

        // Caso 1: el propio barbero tardío de la semana.
        if ((int) $turno->IdBarbero === (int) $barbero->IdBarbero) {
            $diaDescansoBarbero = $this->diaDescansoDe($barbero, $semana, $anio);

            // Si justo ese día le toca su día libre, ese día no aplica
            // almuerzo tardío (no trabaja), vuelve al horario normal.
            if ($diaDescansoBarbero === $diaSemana) {
                return ['12:00', '13:00'];
            }

            return ['13:00', '14:00'];
        }

        // Caso 2: barbero sustituto, únicamente el día asignado.
        if (
            $turno->IdBarberoSustituto &&
            (int) $turno->IdBarberoSustituto === (int) $barbero->IdBarbero &&
            $turno->DiaSustituto === $diaSemana
        ) {
            return ['13:00', '14:00'];
        }

        // Resto de barberos: almuerzo general.
        return ['12:00', '13:00'];
    }

    /**
     * Día de descanso semanal (DiaDescanso=1) configurado para el barbero
     * en su HorariosSemanales/Horarios de esa semana/año, o null si no tiene.
     */
    public function diaDescansoDe(Barbero $barbero, int $semana, int $anio): ?string
    {
        $horarioSemanal = HorarioSemanal::where('IdBarbero', $barbero->IdBarbero)
            ->where('Semana', $semana)
            ->where('Año', $anio)
            ->first();

        if (!$horarioSemanal) {
            return null;
        }

        $diaDescanso = Horario::where('IdHorarioSemanal', $horarioSemanal->IdHorarioSemanal)
            ->where('DiaDescanso', 1)
            ->first();

        return $diaDescanso?->DiaSemana;
    }

    /**
     * Selección aleatoria semanal del barbero con almuerzo tardío.
     * Pensado para ejecutarse por un Command programado cada lunes.
     * Garantiza no repetir el mismo barbero de la semana inmediatamente anterior.
     */
    public function seleccionarBarberoTardioParaSemana(int $semana, int $anio): TurnoAlmuerzoTardio
    {
        $existente = TurnoAlmuerzoTardio::where('Semana', $semana)->where('Año', $anio)->first();
        if ($existente) {
            return $existente;
        }

        $semanaAnterior = $semana - 1;
        $anioAnterior = $anio;
        if ($semanaAnterior < 1) {
            $semanaAnterior = 52;
            $anioAnterior -= 1;
        }

        $idBarberoSemanaAnterior = TurnoAlmuerzoTardio::where('Semana', $semanaAnterior)
            ->where('Año', $anioAnterior)
            ->value('IdBarbero');

        $candidatos = Barbero::where('EstadoA', 1)
            ->when($idBarberoSemanaAnterior, fn ($q) => $q->where('IdBarbero', '<>', $idBarberoSemanaAnterior))
            ->pluck('IdBarbero');

        if ($candidatos->isEmpty()) {
            $candidatos = Barbero::where('EstadoA', 1)->pluck('IdBarbero');
        }

        $idBarberoElegido = $candidatos->random();
        $barberoElegido = Barbero::find($idBarberoElegido);

        // Determinar si ese barbero descansa algún día esta semana, para
        // asignarle de inmediato un sustituto SOLO para ese día.
        $diaDescanso = $this->diaDescansoDe($barberoElegido, $semana, $anio);
        $idSustituto = null;

        if ($diaDescanso) {
            $idSustituto = Barbero::where('EstadoA', 1)
                ->where('IdBarbero', '<>', $idBarberoElegido)
                ->inRandomOrder()
                ->value('IdBarbero');
        }

        return TurnoAlmuerzoTardio::create([
            'IdBarbero' => $idBarberoElegido,
            'IdBarberoSustituto' => $idSustituto,
            'DiaSustituto' => $diaDescanso,
            'Semana' => $semana,
            'Año' => $anio,
            'EstadoA' => 1,
            'FechaA' => now(),
            'UsuarioA' => 1,
        ]);
    }
}
