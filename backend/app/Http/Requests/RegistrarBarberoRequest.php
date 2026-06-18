<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarBarberoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre1'       => 'required|string|max:50',
            'nombre2'       => 'nullable|string|max:50',
            'apellido1'     => 'required|string|max:50',
            'apellido2'     => 'nullable|string|max:50',
            'correo'        => 'required|email|max:100',
            'contrasena'    => 'required|string|min:6|max:255',
            'fecha_ingreso' => 'required|date|before_or_equal:today',
            'dias'          => 'required|array|min:1',
            'dias.*.dia'         => 'required|string',
            'dias.*.hora_entrada'=> 'required|date_format:H:i',
            'dias.*.hora_salida' => 'required|date_format:H:i|after:dias.*.hora_entrada',
            'dias.*.dia_descanso'=> 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre1.required'       => 'El primer nombre es obligatorio',
            'apellido1.required'     => 'El primer apellido es obligatorio',
            'correo.required'        => 'El correo es obligatorio',
            'correo.email'           => 'El correo no tiene un formato válido',
            'contrasena.required'    => 'La contraseña es obligatoria',
            'contrasena.min'         => 'La contraseña debe tener al menos 6 caracteres',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura',
            'dias.required'          => 'Debe configurar al menos un día de horario',
            'dias.min'               => 'Debe configurar al menos un día de horario',
        ];
    }
}
