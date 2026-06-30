<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\HorarioBarbero;
use App\Services\HorarioSemanalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HorarioSemanalController extends Controller
{
    public function __construct(private HorarioSemanalService $servicio) {}

    /**
     * GET /api/admin/horarios-semana?semana=&ano=
     * Estado de la semana: si ya está generada, y el descanso de cada barbero.
     */
    public function index(Request $request)
    {
        $semana = (int) $request->query('semana', now()->isoWeek());
        $anio = (int) $request->query('ano', now()->isoWeekYear());

        $inicioSemana = Carbon::now()->setISODate($anio, $semana, 1)->format('Y-m-d');
        $finSemana = Carbon::now()->setISODate($anio, $semana, 7)->format('Y-m-d');

        $barberos = Barbero::with('usuario')
            ->where('EstadoA', 1)
            ->orderBy('FechaIngreso', 'asc')
            ->get();

        $asignaciones = HorarioBarbero::where('FechaInicio', $inicioSemana)
            ->where('FechaFin', $finSemana)
            ->where('EstadoA', 1)
            ->with('horario')
            ->get()
            ->groupBy('IdBarbero');

        $semanaGenerada = $asignaciones->isNotEmpty();

        $listaBarberos = $barberos->map(function ($b) use ($asignaciones) {
            $filasBarbero = $asignaciones->get($b->IdBarbero, collect());
            $descanso = $filasBarbero->first(fn ($f) => $f->horario && $f->horario->DiaDescanso);

            return [
                'id_barbero' => $b->IdBarbero,
                'nombre_completo' => $b->usuario->nombre_completo,
                'fecha_ingreso' => $b->FechaIngreso->format('Y-m-d'),
                'antiguedad_dias' => $b->antiguedad_dias,
                'dia_descanso' => $descanso?->horario->DiaSemana,
            ];
        });

        return response()->json([
            'semana' => $semana,
            'ano' => $anio,
            'fecha_inicio' => $inicioSemana,
            'fecha_fin' => $finSemana,
            'semana_generada' => $semanaGenerada,
            'barberos' => $listaBarberos,
        ], 200);
    }

    /**
     * POST /api/admin/horarios-semana
     * Genera (rota FIFO) el horario de la semana indicada.
     */
    public function store(Request $request)
    {
        $request->validate([
            'semana' => 'required|integer|min:1|max:53',
            'ano' => 'required|integer|min:2020',
        ]);

        $admin = $request->user();

        try {
            $resultado = $this->servicio->generarSemana(
                (int) $request->input('ano'),
                (int) $request->input('semana'),
                $admin->IdUsuario,
                $request->ip()
            );
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'mensaje' => 'Horario semanal generado correctamente',
            ...$resultado,
        ], 201);
    }

    /**
     * PUT /api/admin/horarios-semana/{idBarbero}/descanso
     * Reasigna el día de descanso de un barbero puntual, revalidando cobertura.
     */
    public function update(Request $request, $idBarbero)
    {
        $request->validate([
            'semana' => 'required|integer',
            'ano' => 'required|integer',
            'dia_descanso' => 'nullable|string|in:Lunes,Martes,Miércoles,Jueves',
        ]);

        $admin = $request->user();

        try {
            $this->servicio->reasignarDiaDescanso(
                (int) $idBarbero,
                (int) $request->input('ano'),
                (int) $request->input('semana'),
                $request->input('dia_descanso'),
                $admin->IdUsuario,
                $request->ip()
            );
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['mensaje' => 'Día de descanso actualizado correctamente'], 200);
    }
}