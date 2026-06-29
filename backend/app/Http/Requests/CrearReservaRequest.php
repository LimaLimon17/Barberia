<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Reserva pública, sin necesidad de registro.
    }

    public function rules(): array
    {
        return [
            'cliente.CI' => ['required', 'string', 'max:20'],
            'cliente.Nombre1' => ['required', 'string', 'max:50'],
            'cliente.Apellido1' => ['required', 'string', 'max:50'],
            'cliente.Telefono' => ['required', 'numeric'],
            'cliente.Correo' => ['required', 'email', 'max:100'],

            'id_barbero' => ['required', 'integer', 'exists:Barberos,IdBarbero'],
            'fecha_cita' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],

            'servicios' => ['required', 'array', 'min:1'],
            'servicios.*' => ['integer', 'exists:Servicios,IdServicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente.CI.required' => 'La cédula de identidad es obligatoria.',
            'cliente.Nombre1.required' => 'El nombre es obligatorio.',
            'cliente.Apellido1.required' => 'El apellido es obligatorio.',
            'cliente.Telefono.required' => 'El teléfono es obligatorio.',
            'cliente.Correo.required' => 'El correo electrónico es obligatorio.',
            'id_barbero.required' => 'Debes seleccionar un barbero.',
            'servicios.required' => 'Debes seleccionar al menos un servicio.',
            'hora_inicio.required' => 'Debes seleccionar un horario.',
        ];
    }
}
