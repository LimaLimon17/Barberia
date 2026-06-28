<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearReservaRequest;
use App\Jobs\ExpirarReservaPendiente;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Services\PagoQRService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Categoria;
use App\Models\HorarioSemanal;

use Illuminate\Support\Facades\DB;

//CONTROLLER PARA EL FLUJO DE LA RESERVA 
class ReservaController extends Controller
{
    public function __construct(
        private ReservaService $reservaService,
        private PagoQRService $pagoQRService,
    ) {
    }

    /**
     * FUNCION PARA
     * Paso 1 - Escenario : BUSCAR CLIENTE POR CI Y AUTOCOMPLETAR
     * GET /api/clientes/{ci}
     */
    public function buscarClientePorCI(string $ci)
    {
        $cliente = Cliente::where('CI', $ci)->where('EstadoA', 1)->first();

        return response()->json([
            'encontrado' => (bool) $cliente,
            'cliente' => $cliente,
        ]);
    }

    /**
     * FUNCION PARA:
     * Paso 2 - LISTAR BARBEROS CON SU DISPONIBILIDAD
     * GET /api/barberos/disponibilidad
     */
    public function disponibilidadBarberos()
    {
        $barberos = Barbero::with('usuario')->where('EstadoA', 1)->get();

        $resultado = $barberos->map(function (Barbero $barbero) {
            return [
                'id_barbero' => $barbero->IdBarbero,
                'nombre' => trim($barbero->usuario?->Nombre1 . ' ' . $barbero->usuario?->Apellido1),
                'disponible_ahora' => !$this->reservaService->estaOcupadoAhora($barbero),
            ];
        });

        return response()->json(['barberos' => $resultado]);
    }

    /**
     * FUNCION PARA 
     * Paso 3 - MOSTRAR SERVICIOS ACTIVOS SEGÚN CATEGORIA
     * GET /api/servicios?id_categoria=
     */
    public function categorias()
{
    $categorias = Categoria::where('EstadoA', 1)
        ->select('IdCategoria', 'Nombre')
        ->orderBy('Nombre')
        ->get();

    return response()->json(['categorias' => $categorias]);
}
    public function serviciosPorCategoria(Request $request)
{
    $query = Servicio::with('categoria')->where('EstadoA', 1);

    if ($request->filled('id_categoria')) {
        $query->where('IdCategoria', $request->input('id_categoria'));
    }

    $servicios = $query->get()->map(function ($s) {
        return [
            'IdServicio'      => $s->IdServicio,
            'IdCategoria'     => $s->IdCategoria,
            'NombreCategoria' => $s->categoria?->Nombre,
            'Nombre'          => $s->Nombre,
            'FotoURL'         => $s->FotoURL,
            'Precio'          => $s->Precio,
            'DuracionMinutos' => $s->DuracionMinutos,
        ];
    });

    return response()->json(['servicios' => $servicios]);
}

public function fechaMaximaDisponible(Request $request)
{
    $idBarbero = $request->input('id_barbero');

    $query = HorarioSemanal::where('EstadoA', 1);

    if ($idBarbero) {
        $query->where('IdBarbero', $idBarbero);
    }

    $ultimo = $query->orderByRaw('Año DESC, Semana DESC')->first();

    if (!$ultimo) {
        return response()->json(['fecha_maxima' => now()->toDateString()]);
    }

    // El domingo de esa semana ISO es la fecha máxima reservable
    $fechaMax = Carbon::now()
        ->setISODate($ultimo->Año, $ultimo->Semana, 7) // 7 = domingo
        ->toDateString();

    return response()->json(['fecha_maxima' => $fechaMax]);
}
    /**
     * FUNCIÓN PARA 
     * Paso 3 - MOSTRAR HORARIOS DISPONIBLES SEGÚN BARBERO Y SERVICIO
     * barbero/fecha/duración total seleccionados
     * GET /api/disponibilidad/slots?id_barbero=&fecha=YYYY-MM-DD&servicios[]=
     */
    public function slotsDisponibles(Request $request)
    {
        $validado = Validator::make($request->all(), [
            'id_barbero' => ['required', 'integer', 'exists:Barberos,IdBarbero'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'servicios' => ['required', 'array', 'min:1'],
            'servicios.*' => ['integer', 'exists:Servicios,IdServicio'],
        ])->validate();

        $barbero = Barbero::findOrFail($validado['id_barbero']);
        $servicios = Servicio::whereIn('IdServicio', $validado['servicios'])->get();
        $duracionTotal = $this->reservaService->calcularDuracionTotal($servicios);
        $costoTotal = $this->reservaService->calcularCostoTotal($servicios);

        $fecha = Carbon::parse($validado['fecha']);
        $slots = $this->reservaService->obtenerSlotsDisponibles($barbero, $fecha, $duracionTotal);

        return response()->json([
            'duracion_total_minutos' => $duracionTotal,
            'costo_total' => $costoTotal,
            'monto_anticipo' => round($costoTotal * ReservaService::PORCENTAJE_ANTICIPO, 2),
            'slots' => $slots,
        ]);
    }

    /**
     * FUNCION PARA
     * Paso 3->4 - REGISTRAR LA RESERVA COMO PENDIENTE, DARLE 15 MINUTOS
     * Y MOSTRAR QR
     * POST /api/reservas
     */
    public function store(CrearReservaRequest $request)
    {
        $datos = $request->validated();

        $cliente = Cliente::updateOrCreate(
            ['CI' => $datos['cliente']['CI']],
            [
                'Nombre1' => $datos['cliente']['Nombre1'],
                'Apellido1' => $datos['cliente']['Apellido1'],
                'Telefono' => $datos['cliente']['Telefono'],
                'Correo' => $datos['cliente']['Correo'],
                'EstadoA' => 1,
                'FechaA' => now(),
                'UsuarioA' => 1,
            ]
        );

        $barbero = Barbero::findOrFail($datos['id_barbero']);
        $servicios = Servicio::whereIn('IdServicio', $datos['servicios'])->get();
        $fecha = Carbon::parse($datos['fecha_cita']);

        $reserva = $this->reservaService->crearReservaPendiente(
            $cliente->toArray(),
            $barbero,
            $servicios,
            $fecha,
            $datos['hora_inicio']
        );

        ExpirarReservaPendiente::dispatch($reserva->IdReserva)
            ->delay(now()->addMinutes(ReservaService::MINUTOS_EXPIRACION_PAGO));

        $qr = $this->pagoQRService->generarQR($reserva);

        return response()->json([
            'reserva' => $reserva->load('servicios', 'barbero.usuario'),
            'qr' => $qr,
            'duracion_total_minutos' => $reserva->servicios->sum('DuracionMinutos') + ReservaService::MINUTOS_LIMPIEZA + ReservaService::MINUTOS_TOLERANCIA,
        ], 201);
    }

    /**
     * FUNCION PARA 
     * Paso 4 - CONFIMAR PAGO 
     * POST /api/reservas/{idReserva}/confirmar-pago
     */
    public function confirmarPago(Request $request, int $idReserva)
    {
        $reserva = Reserva::findOrFail($idReserva);
        $metodoPago = $request->input('metodo_pago', 'QR');

        $reservaConfirmada = $this->reservaService->confirmarPago($reserva, $metodoPago);

        return response()->json([
            'reserva' => $reservaConfirmada->load('servicios', 'barbero.usuario', 'cliente'),
        ]);
    }

    /**
     * FUNCION PARA
     * Paso 4 - CONTADOR REGRESIVO Y DETECTAR EXPIRACIÓN 
     * GET /api/reservas/{idReserva}/estado
     */
    public function estado(int $idReserva)
    {
        $reserva = Reserva::findOrFail($idReserva);

        $this->reservaService->expirarSiCorresponde($reserva);
        $reserva->refresh();

        $segundosRestantes = max(
            0,
            (ReservaService::MINUTOS_EXPIRACION_PAGO * 60) - Carbon::parse($reserva->FechaA)->diffInSeconds(now())
        );

        return response()->json([
            'estado' => $reserva->EstadoReserva,
            'segundos_restantes' => $reserva->EstadoReserva === ReservaService::PENDIENTE ? $segundosRestantes : 0,
        ]);
    }
}
