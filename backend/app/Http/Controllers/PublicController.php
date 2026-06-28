<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Barbero;
use App\Models\Servicio;
use App\Models\Cliente;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Retorna categorías, servicios con fotoURL y barberos activos para la pagina publica.
     */
    public function obtenerCatalogoHome()
    {
        try {
            $categorias = Categoria::where('EstadoA', 1)->get();
            
            $servicios = Servicio::where('EstadoA', 1)
                ->select('IdServicio', 'IdCategoria', 'Nombre', 'Precio', 'DuracionMinutos', 'FotoURL')
                ->get()
                ->map(fn($s) => [
                        'IdServicio' => $s->IdServicio,
                        'IdCategoria' => $s->IdCategoria,
                        'Nombre' => $s->Nombre,
                        'Precio' => $s->Precio,
                        'DuracionMinutos' => $s->DuracionMinutos,
                        'FotoURL' => $s->FotoURL,
               
 ]);
            // Carga barberos con los datos de su usuario relacionado
            $barberos = Barbero::where('EstadoA', 1)
    ->with(['usuario' => function($q) {          // ← 'usuario', no 'user'
        $q->select('IdUsuario', 'Nombre1', 'Apellido1');
    }])
    ->get()
    ->map(function($b) {
        return [
            'IdBarbero' => $b->IdBarbero,
            'Nombre1'   => $b->usuario->Nombre1,  // ← $b->usuario
            'Apellido1' => $b->usuario->Apellido1
        ];
    });

            return response()->json([
                'categorias' => $categorias,
                'servicios' => $servicios,
                'barberos' => $barberos
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo compilar el catálogo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Escenario de búsqueda de historial por CI
     */
    public function buscarClientePorCI($ci)
    {
        $cliente = Cliente::where('CI', $ci)->where('EstadoA', 1)->first();
        
        if ($cliente) {
            return response()->json($cliente, 200);
        }
        
        return response()->json(['nuevo' => true], 200);
    }
}