<?php

namespace App\Services;

use App\Models\AuditoriaGeneral;
use Illuminate\Support\Carbon;

class AuditoriaService
{
    public function registrar(
        string $tabla,
        string|int|null $registroId,
        string $accion,
        ?string $campo = null,
        mixed $valorAnterior = null,
        mixed $valorNuevo = null,
        ?int $usuarioId = null,
        ?string $detalles = null
    ): AuditoriaGeneral {
        return AuditoriaGeneral::create([
            'TablaNombre' => $tabla,
            'RegistroId' => $registroId !== null ? (string) $registroId : null,
            'Accion' => $accion,
            'Campo' => $campo,
            'ValorAnterior' => $this->normalizarValor($valorAnterior),
            'ValorNuevo' => $this->normalizarValor($valorNuevo),
            'UsuarioA' => $usuarioId,
            'FechaA' => Carbon::now(),
            'DireccionIP' => request()?->ip(),
            'Detalles' => $detalles,
        ]);
    }

    private function normalizarValor(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $valor;
    }
}
