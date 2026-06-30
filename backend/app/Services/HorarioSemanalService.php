<?php

namespace App\Services;

use App\Models\Barbero;
use App\Models\Horario;
use App\Models\HorarioBarbero;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HorarioSemanalService
{
    public const DIAS_DESCANSO_POSIBLES = ['Lunes', 'Martes', 'Miércoles', 'Jueves'];
    public const DIAS_SEMANA_COMPLETA = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public const HORA_APERTURA = '10:00:00';
    public const HORA_CIERRE = '22:00:00';
    public const MINIMO_HORAS_LABORALES = 8;

    /**
     * RF14/RF27: Genera (rota) el horario de TODOS los barberos activos
     * para la semana ISO indicada, asignando descanso FIFO Lunes-Jueves.
     */
    public function generarSemana(int $anio, int $semanaIso, int $idAdmin, ?string $ip): array
    {
        $barberos = Barbero::where('EstadoA', 1)
            ->orderBy('FechaIngreso', 'asc')
            ->orderBy('IdBarbero', 'asc')
            ->get();

        if ($barberos->isEmpty()) {
            throw ValidationException::withMessages([
                'barberos' => 'No hay barberos activos para generar el horario.',
            ]);
        }

        $inicioSemana = Carbon::now()->setISODate($anio, $semanaIso, 1)->format('Y-m-d');
        $finSemana = Carbon::now()->setISODate($anio, $semanaIso, 7)->format('Y-m-d');

        $yaExiste = HorarioBarbero::where('FechaInicio', $inicioSemana)
            ->where('FechaFin', $finSemana)
            ->where('EstadoA', 1)
            ->exists();

        if ($yaExiste) {
            throw ValidationException::withMessages([
                'semana' => 'Ya existe un horario generado para esta semana. Use la opción de reasignar un día puntual si necesita un ajuste.',
            ]);
        }

        $asignaciones = $this->calcularRotacionFifo($barberos, $anio, $semanaIso);
        $this->validarCobertura($asignaciones, $barberos->count());

        DB::transaction(function () use ($barberos, $asignaciones, $inicioSemana, $finSemana, $idAdmin) {
            foreach ($barberos as $barbero) {
                $this->crearAsignacionSemanal($barbero, $asignaciones[$barbero->IdBarbero], $inicioSemana, $finSemana, $idAdmin);
            }
        });

        return [
            'fecha_inicio' => $inicioSemana,
            'fecha_fin' => $finSemana,
            'asignaciones' => $barberos->map(fn ($b) => [
                'id_barbero' => $b->IdBarbero,
                'nombre' => trim($b->usuario->Nombre1 . ' ' . $b->usuario->Apellido1),
                'dia_descanso' => $asignaciones[$b->IdBarbero],
            ]),
        ];
    }

    /**
     * Asigna un barbero RECIÉN REGISTRADO al horario de la semana actual,
     * sin día de descanso (trabaja todos los días hasta la próxima rotación
     * FIFO regular, que ya lo incluirá ordenado por su antigüedad real).
     */
    public function asignarBarberoNuevoSemanaActual(Barbero $barbero, int $idAdmin, ?string $ip): void
    {
        $anio = now()->isoWeekYear();
        $semana = now()->isoWeek();
        $inicioSemana = Carbon::now()->setISODate($anio, $semana, 1)->format('Y-m-d');
        $finSemana = Carbon::now()->setISODate($anio, $semana, 7)->format('Y-m-d');

        $this->crearAsignacionSemanal($barbero, null, $inicioSemana, $finSemana, $idAdmin);
    }

    /**
     * Reasigna el día de descanso de UN barbero en una semana ya generada,
     * revalidando cobertura antes de aplicar el cambio.
     */
    public function reasignarDiaDescanso(int $idBarbero, int $anio, int $semanaIso, ?string $nuevoDiaDescanso, int $idAdmin, ?string $ip): void
    {
        if ($nuevoDiaDescanso !== null && !in_array($nuevoDiaDescanso, self::DIAS_DESCANSO_POSIBLES, true)) {
            throw ValidationException::withMessages([
                'dia_descanso' => 'El día de descanso debe ser Lunes, Martes, Miércoles o Jueves.',
            ]);
        }

        $barbero = Barbero::where('IdBarbero', $idBarbero)->where('EstadoA', 1)->firstOrFail();

        $inicioSemana = Carbon::now()->setISODate($anio, $semanaIso, 1)->format('Y-m-d');
        $finSemana = Carbon::now()->setISODate($anio, $semanaIso, 7)->format('Y-m-d');

        // Validar cobertura simulando el cambio sobre las asignaciones vigentes de TODOS.
        $asignacionesActuales = $this->obtenerAsignacionesDeSemana($anio, $semanaIso);
        $asignacionesActuales[$idBarbero] = $nuevoDiaDescanso;
        $this->validarCobertura($asignacionesActuales, count($asignacionesActuales));

        DB::transaction(function () use ($barbero, $nuevoDiaDescanso, $inicioSemana, $finSemana, $idAdmin) {
            // Desactivar las asignaciones vigentes de esa semana para este barbero...
            HorarioBarbero::where('IdBarbero', $barbero->IdBarbero)
                ->where('FechaInicio', $inicioSemana)
                ->where('FechaFin', $finSemana)
                ->where('EstadoA', 1)
                ->update(['EstadoA' => 0]);

            // ...y crear las nuevas con el día de descanso actualizado.
            $this->crearAsignacionSemanal($barbero, $nuevoDiaDescanso, $inicioSemana, $finSemana, $idAdmin);
        });
    }

    // ── Helper: rotación FIFO pura (sin tocar BD) ──
    // ── Helper: asignación FIFO pura por antigüedad (sin tocar BD) ──
// Ya NO depende de la semana: el rango de antigüedad determina el día
// fijo de descanso. Solo cambia si entra/sale un barbero del equipo.
private function calcularRotacionFifo(Collection $barberos, int $anio, int $semanaIso): array
{
    $n = $barberos->count();
    $asignaciones = [];

    foreach ($barberos->values() as $rank => $barbero) {
        if ($n < 2) {
            // Un solo barbero activo: no puede descansar, dejaría la barbería cerrada.
            $asignaciones[$barbero->IdBarbero] = null;
            continue;
        }
        $slot = $rank % 4;
        $asignaciones[$barbero->IdBarbero] = self::DIAS_DESCANSO_POSIBLES[$slot];
    }

    return $asignaciones;
}

    // ── Helper: valida que cada día Lun-Jue quede con al menos 1 trabajando ──
    private function validarCobertura(array $asignaciones, int $totalBarberos): void
    {
        foreach (self::DIAS_DESCANSO_POSIBLES as $dia) {
            $descansanEseDia = count(array_filter($asignaciones, fn ($d) => $d === $dia));
            if ($totalBarberos - $descansanEseDia < 1) {
                throw ValidationException::withMessages([
                    'cobertura' => "El día {$dia} quedaría sin ningún barbero trabajando. Reasigne los descansos antes de confirmar.",
                ]);
            }
        }
    }

    // ── Helper: crea los 7 registros (uno por día) en HorariosBarberos para una semana ──
    private function crearAsignacionSemanal(Barbero $barbero, ?string $diaDescanso, string $inicioSemana, string $finSemana, int $idAdmin): void
    {
        foreach (self::DIAS_SEMANA_COMPLETA as $dia) {
            $esDescanso = $dia === $diaDescanso;

            $horario = $this->obtenerOCrearHorario(
                $dia,
                $esDescanso ? null : self::HORA_APERTURA,
                $esDescanso ? null : self::HORA_CIERRE,
                $esDescanso,
                $idAdmin
            );

            HorarioBarbero::create([
                'IdBarbero' => $barbero->IdBarbero,
                'IdHorario' => $horario->IdHorario,
                'FechaInicio' => $inicioSemana,
                'FechaFin' => $finSemana,
                'EstadoA' => 1,
                'FechaA' => now(),
                'UsuarioA' => $idAdmin,
            ]);
        }
    }

    // ── Helper: reutiliza una plantilla de Horario existente o la crea ──
    private function obtenerOCrearHorario(string $dia, ?string $entrada, ?string $salida, bool $descanso, int $idAdmin): Horario
    {
        $query = Horario::where('DiaSemana', $dia)->where('DiaDescanso', $descanso ? 1 : 0);
        $entrada === null ? $query->whereNull('HoraEntrada') : $query->where('HoraEntrada', $entrada);
        $salida === null ? $query->whereNull('HoraSalida') : $query->where('HoraSalida', $salida);

        $horario = $query->first();
        if ($horario) {
            return $horario;
        }

        return Horario::create([
            'DiaSemana' => $dia,
            'HoraEntrada' => $entrada,
            'HoraSalida' => $salida,
            'DiaDescanso' => $descanso ? 1 : 0,
            'EstadoA' => 1,
            'FechaA' => now(),
            'UsuarioA' => $idAdmin,
        ]);
    }

    // ── Helper: lee las asignaciones vigentes de descanso de una semana ya generada ──
    private function obtenerAsignacionesDeSemana(int $anio, int $semanaIso): array
    {
        $inicioSemana = Carbon::now()->setISODate($anio, $semanaIso, 1)->format('Y-m-d');
        $finSemana = Carbon::now()->setISODate($anio, $semanaIso, 7)->format('Y-m-d');

        $asignaciones = HorarioBarbero::where('FechaInicio', $inicioSemana)
            ->where('FechaFin', $finSemana)
            ->where('EstadoA', 1)
            ->with('horario')
            ->get()
            ->groupBy('IdBarbero')
            ->map(function ($filas) {
                $descanso = $filas->first(fn ($f) => $f->horario && $f->horario->DiaDescanso);
                return $descanso?->horario->DiaSemana;
            })
            ->toArray();

        return $asignaciones;
    }
}