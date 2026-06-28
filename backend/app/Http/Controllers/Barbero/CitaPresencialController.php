<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Jobs\ExpirarReservaPendiente;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\ReservaServicio;
use App\Models\Servicio;
use App\Models\Categoria;
use App\Services\PagoQRService;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * CitaPresencialController
 * ─────────────────────────────────────────────────────────────────
 * El barbero autenticado crea una cita para un cliente presencial.
 * - No elige barbero: siempre es él mismo.
 * - Pago Efectivo: se confirma de inmediato (100% en el momento).
 * - Pago QR: se crea Pendiente + se genera el QR; se confirma luego
 *   con un endpoint separado cuando el cliente ya pagó.
 * - Los triggers MySQL auditan automáticamente los INSERTs/UPDATEs.
 */
class CitaPresencialController extends Controller
{
    public function __construct(
        private ReservaService $reservaService,
        private PagoQRService $pagoQRService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/cita-presencial/inicializar
    // ──────────────────────────────────────────────────────────────
    public function inicializar(Request $request)
    {
        $barbero = $this->barberoAutenticado($request);

        $categorias = Categoria::where('EstadoA', 1)
            ->select('IdCategoria', 'Nombre')
            ->orderBy('Nombre')
            ->get();

        return response()->json([
            'id_barbero'  => $barbero->IdBarbero,
            'nombre'      => trim($barbero->usuario->Nombre1 . ' ' . $barbero->usuario->Apellido1),
            'categorias'  => $categorias,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/cita-presencial/servicios?id_categoria=X
    // ──────────────────────────────────────────────────────────────
    public function servicios(Request $request)
    {
        $query = Servicio::where('EstadoA', 1);

        if ($request->filled('id_categoria')) {
            $query->where('IdCategoria', $request->id_categoria);
        }

        $servicios = $query->get()->map(fn($s) => [
            'IdServicio'      => $s->IdServicio,
            'IdCategoria'     => $s->IdCategoria,
            'Nombre'          => $s->Nombre,
            'Precio'          => $s->Precio,
            'DuracionMinutos' => $s->DuracionMinutos,
            'FotoURL'         => $s->FotoURL,
        ]);

        return response()->json(['servicios' => $servicios]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/cita-presencial/slots?fecha=YYYY-MM-DD&servicios[]=X
    // ──────────────────────────────────────────────────────────────
    public function slots(Request $request)
    {
        $request->validate([
            'fecha'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'servicios'   => 'required|array|min:1',
            'servicios.*' => 'integer|exists:Servicios,IdServicio',
        ]);

        $barbero   = $this->barberoAutenticado($request);
        $servicios = Servicio::whereIn('IdServicio', $request->servicios)->where('EstadoA', 1)->get();

        $duracionTotal = $this->reservaService->calcularDuracionTotal($servicios);
        $costoTotal    = $this->reservaService->calcularCostoTotal($servicios);
        $fecha         = Carbon::parse($request->fecha);
        $slots         = $this->reservaService->obtenerSlotsDisponibles($barbero, $fecha, $duracionTotal);

        return response()->json([
            'duracion_total_minutos' => $duracionTotal,
            'costo_total'            => $costoTotal,
            'slots'                  => $slots,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/cita-presencial/crear
    // Efectivo/Tarjeta → confirma de inmediato.
    // QR → crea Pendiente y devuelve el QR para cobrar.
    // ──────────────────────────────────────────────────────────────
    public function crear(Request $request)
    {
        $request->validate([
            'ci'             => 'required|string|max:20',
            'nombre1'        => 'required|string|max:50',
            'nombre2'        => 'nullable|string|max:50',
            'apellido1'      => 'required|string|max:50',
            'apellido2'      => 'nullable|string|max:50',
            'telefono'       => 'nullable|integer',
            'correo'         => 'nullable|email|max:100',
            'servicios'      => 'required|array|min:1',
            'servicios.*'    => 'integer|exists:Servicios,IdServicio',
            'fecha'          => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_inicio'    => 'required|date_format:H:i',
            'metodo_pago'    => 'required|in:Efectivo,QR,Tarjeta',
        ], [
            'ci.required'          => 'El CI del cliente es obligatorio.',
            'nombre1.required'     => 'El primer nombre es obligatorio.',
            'apellido1.required'   => 'El primer apellido es obligatorio.',
            'servicios.required'   => 'Debe seleccionar al menos un servicio.',
            'fecha.required'       => 'La fecha de la cita es obligatoria.',
            'hora_inicio.required' => 'El horario de inicio es obligatorio.',
            'metodo_pago.required' => 'Debe seleccionar el método de pago.',
        ]);

        $barbero = $this->barberoAutenticado($request);

        $serviciosDB = Servicio::whereIn('IdServicio', $request->servicios)
            ->where('EstadoA', 1)->get();

        if ($serviciosDB->count() !== count($request->servicios)) {
            return response()->json(['error' => 'Uno o más servicios no son válidos.'], 422);
        }

        $duracion = $this->reservaService->calcularDuracionTotal($serviciosDB);
        $costo    = $this->reservaService->calcularCostoTotal($serviciosDB);

        $inicio   = strtotime($request->hora_inicio . ':00');
        $fin      = $inicio + ($duracion * 60);
        $hora_fin = date('H:i:s', $fin);

        if ($inicio < strtotime('10:00:00') || $fin > strtotime('22:00:00')) {
            return response()->json([
                'error' => 'La cita queda fuera del horario operativo (10:00 – 22:00).'
            ], 422);
        }

        $conflicto = Reserva::where('IdBarbero', $barbero->IdBarbero)
            ->where('FechaCita', $request->fecha)
            ->whereIn('EstadoReserva', ['Pendiente', 'Confirmada'])
            ->where(function ($q) use ($request, $hora_fin) {
                $q->where('HoraInicio', '<', $hora_fin)
                  ->where('HoraFin',    '>', $request->hora_inicio . ':00');
            })->exists();

        if ($conflicto) {
            return response()->json([
                'error' => 'Ya tienes una cita en ese bloque horario. Elige otro horario.'
            ], 409);
        }

        DB::statement("SET @v_auditoria_ip = ?", [$request->ip()]);
        $idUsuarioAutenticado = $request->user()->IdUsuario;
        $esQR = $request->metodo_pago === 'QR';

        try {
            DB::beginTransaction();

            $cliente = Cliente::updateOrCreate(
                ['CI' => $request->ci],
                [
                    'Nombre1'   => $request->nombre1,
                    'Nombre2'   => $request->nombre2,
                    'Apellido1' => $request->apellido1,
                    'Apellido2' => $request->apellido2,
                    'Telefono'  => $request->telefono,
                    'Correo'    => $request->correo,
                    'EstadoA'   => 1,
                    'FechaA'    => now(),
                    'UsuarioA'  => $idUsuarioAutenticado,
                ]
            );

            $reserva = Reserva::create([
                'IdCliente'         => $cliente->CI,
                'IdBarbero'         => $barbero->IdBarbero,
                'FechaCita'         => $request->fecha,
                'HoraInicio'        => $request->hora_inicio . ':00',
                'HoraFin'           => $hora_fin,
                'CostoTotal'        => $costo,
                'MontoAnticipo'     => $costo, // 100% pagado (presencial)
                'EstadoReserva'     => $esQR ? ReservaService::PENDIENTE : 'Confirmada',
                'FechaPagoAnticipo' => $esQR ? null : now(),
                'MetodoPagoFinal'   => $request->metodo_pago,
                'EstadoA'           => 1,
                'FechaA'            => now(),
                'UsuarioA'          => $idUsuarioAutenticado,
            ]);

            foreach ($request->servicios as $idServicio) {
                ReservaServicio::create([
                    'IdServicio' => $idServicio,
                    'IdReserva'  => $reserva->IdReserva,
                    'EstadoA'    => 1,
                    'FechaA'     => now(),
                    'UsuarioA'   => $idUsuarioAutenticado,
                ]);
            }

            DB::commit();

            // ── Flujo QR: queda pendiente, se manda el QR para cobrar ──
            if ($esQR) {
                ExpirarReservaPendiente::dispatch($reserva->IdReserva)
                    ->delay(now()->addMinutes(ReservaService::MINUTOS_EXPIRACION_PAGO));

                $qr = $this->pagoQRService->generarQR($reserva);

                return response()->json([
                    'pendiente'   => true,
                    'id_reserva'  => $reserva->IdReserva,
                    'qr'          => $qr,
                    'costo_total' => $costo,
                    'hora_fin'    => $hora_fin,
                ], 201);
            }

            // ── Flujo Efectivo/Tarjeta: ya queda confirmada ──
            return response()->json([
                'success'     => true,
                'id_reserva'  => $reserva->IdReserva,
                'costo_total' => $costo,
                'hora_fin'    => $hora_fin,
                'metodo_pago' => $request->metodo_pago,
                'cliente'     => $cliente->only(['CI', 'Nombre1', 'Apellido1']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // POST /api/barbero/cita-presencial/{idReserva}/confirmar-pago
    // El barbero confirma que el cliente ya pagó el QR.
    // ──────────────────────────────────────────────────────────────
    public function confirmarPago(Request $request, int $idReserva)
    {
        $barbero = $this->barberoAutenticado($request);

        try {
            $reserva = DB::transaction(function () use ($barbero, $idReserva, $request) {
                $reservaLock = Reserva::where('IdReserva', $idReserva)
                    ->where('IdBarbero', $barbero->IdBarbero)
                    ->lockForUpdate()
                    ->first();

                if (!$reservaLock) {
                    throw ValidationException::withMessages([
                        'id_reserva' => 'La reserva no existe o no pertenece a este barbero.',
                    ]);
                }

                if ($reservaLock->EstadoReserva !== ReservaService::PENDIENTE) {
                    throw ValidationException::withMessages([
                        'estado' => 'Esta cita ya no está pendiente de pago.',
                    ]);
                }

                $reservaLock->update([
                    'EstadoReserva'     => 'Confirmada',
                    'FechaPagoAnticipo' => now(),
                ]);

                return $reservaLock->fresh();
            });
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'success'     => true,
            'id_reserva'  => $reserva->IdReserva,
            'costo_total' => (float) $reserva->CostoTotal,
            'hora_fin'    => $reserva->HoraFin,
            'metodo_pago' => $reserva->MetodoPagoFinal,
            'cliente'     => Cliente::where('CI', $reserva->IdCliente)->first()?->only(['CI', 'Nombre1', 'Apellido1']),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /api/barbero/cita-presencial/citas?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
    // Listado simple para que el barbero confirme qué citas se crearon.
    // ──────────────────────────────────────────────────────────────
    public function misCitas(Request $request)
    {
        $request->validate([
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
        ]);

        $barbero = $this->barberoAutenticado($request);

        $citas = Reserva::where('IdBarbero', $barbero->IdBarbero)
            ->whereBetween('FechaCita', [$request->desde, $request->hasta])
            ->with(['cliente', 'servicios'])
            ->orderBy('FechaCita')
            ->orderBy('HoraInicio')
            ->get()
            ->map(fn ($r) => [
                'id_reserva'   => $r->IdReserva,
                'fecha'        => $r->FechaCita,
                'hora_inicio'  => $r->HoraInicio,
                'hora_fin'     => $r->HoraFin,
                'estado'       => $r->EstadoReserva,
                'metodo_pago'  => $r->MetodoPagoFinal,
                'costo_total'  => (float) $r->CostoTotal,
                'cliente'      => trim(($r->cliente->Nombre1 ?? '') . ' ' . ($r->cliente->Apellido1 ?? '')),
                'servicios'    => $r->servicios->pluck('Nombre'),
            ]);

        return response()->json(['citas' => $citas]);
    }

    // ── Helper: obtiene el Barbero del usuario autenticado ────────
    private function barberoAutenticado(Request $request): Barbero
    {
        return Barbero::where('IdUsuario', $request->user()->IdUsuario)
            ->where('EstadoA', 1)
            ->with(['usuario' => fn($q) => $q->select('IdUsuario', 'Nombre1', 'Apellido1')])
            ->firstOrFail();
    }
}