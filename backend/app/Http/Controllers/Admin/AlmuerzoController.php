<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmuerzoController extends Controller
{
    /**
     * Ver registros de almuerzo de un barbero en la semana actual o una específica.
     */
    public function index(Request $request, $idBarbero)
    {
        // Si piden semana específica la usamos, si no la semana actual
        $fechaInicio = $request->query('fecha_inicio',
            now()->startOfWeek()->toDateString()
        );
        $fechaFin = $request->query('fecha_fin',
            now()->endOfWeek()->toDateString()
        );

        $registros = DB::table('Registros as r')
            ->join('Barberos as b', 'r.IdBarbero', '=', 'b.IdBarbero')
            ->join('Usuarios as u', 'b.IdUsuario', '=', 'u.IdUsuario')
            ->where('r.IdBarbero', $idBarbero)
            ->where('r.EstadoA', 1)
            ->whereBetween('r.Fecha', [$fechaInicio, $fechaFin])
            ->orderBy('r.Fecha', 'asc')
            ->orderBy('r.HoraInicio', 'asc')
            ->select(
                'r.IdRegistro as id_registro',
                'r.Fecha as fecha',
                'r.HoraInicio as hora_salida', // Alias para el frontend
                'r.HoraFin as hora_retorno',   // Alias para el frontend
                'r.Observacion as observacion',
                DB::raw("CONCAT(u.Nombre1, ' ', u.Apellido1) as nombre_barbero")
            )
            ->get();

        return response()->json([
            'id_barbero'   => (int) $idBarbero,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'registros'    => $registros,
        ], 200);
    }

    /**
     * Registrar salida a almuerzo.
     */
    public function store(Request $request, $idBarbero)
    {
        $request->validate([
            'fecha'       => 'required|date',
            'hora_salida' => 'required|date_format:H:i',
            'observacion' => 'nullable|string|max:255',
        ]);

        DB::table('Registros')->insert([
            'IdBarbero'   => $idBarbero,
            'Fecha'       => $request->fecha,
            'HoraInicio'  => $request->hora_salida, // Hora de salida es HoraInicio en BD
            'HoraFin'     => null,
            'Observacion' => $request->observacion,
            'Ausencia'    => 1, // Asumimos que 1 = Ausencia o registro activo de almuerzo
            'EstadoA'     => 1,
            'FechaA'      => now(),
            'UsuarioA'    => $request->user()->IdUsuario,
        ]);

        return response()->json([
            'mensaje' => 'Salida a almuerzo registrada correctamente',
        ], 201);
    }

    /**
     * Registrar retorno de almuerzo.
     */
    public function update(Request $request, $idBarbero, $idRegistro)
    {
        $request->validate([
            'hora_retorno' => 'required|date_format:H:i',
        ]);

        $actualizado = DB::table('Registros')
            ->where('IdRegistro', $idRegistro)
            ->where('IdBarbero', $idBarbero)
            ->whereNull('HoraFin')
            ->update(['HoraFin' => $request->hora_retorno]);

        if (!$actualizado) {
            return response()->json([
                'mensaje' => 'Registro no encontrado o ya tiene hora de retorno',
            ], 404);
        }

        return response()->json([
            'mensaje' => 'Retorno de almuerzo registrado correctamente',
        ], 200);
    }
}
