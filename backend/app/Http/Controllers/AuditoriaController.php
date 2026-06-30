<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditoriaController extends Controller
{
    /**
     * Registra una auditoría cada vez que un usuario genera o visualiza un reporte
     */
    public function registrarReporte(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|string|max:100',
            'filtros' => 'nullable|array',
        ]);

        try {
            $usuario = $request->user();
            $detalles = 'Se generó el reporte: ' . $request->tipo_reporte;
            
            if ($request->has('filtros') && !empty($request->filtros)) {
                $detalles .= ' | Filtros: ' . json_encode($request->filtros, JSON_UNESCAPED_UNICODE);
            }

            // Limitar longitud de detalles al límite de la columna (varchar 500)
            $detalles = substr($detalles, 0, 495);

            DB::table('AuditoriaGeneral')->insert([
                'TablaNombre' => 'Varias (Reportes)',
                'RegistroId' => 'N/A',
                'Accion' => 'GENERACION_REPORTE',
                'Campo' => 'Exportación / Vista Previa PDF',
                'ValorAnterior' => null,
                'ValorNuevo' => null,
                'UsuarioA' => $usuario ? $usuario->IdUsuario : null,
                'FechaA' => now(),
                'DireccionIP' => $request->ip(),
                'Detalles' => $detalles
            ]);

            return response()->json(['message' => 'Auditoría registrada exitosamente'], 201);
        } catch (\Exception $e) {
            Log::error('Error registrando auditoría de reporte: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno al registrar auditoría'], 500);
        }
    }
}
