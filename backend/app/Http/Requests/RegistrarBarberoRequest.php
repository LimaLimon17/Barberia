<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\HorarioSemanalService;

class RegistrarBarberoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre1'       => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'nombre2'       => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'apellido1'     => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'apellido2'     => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'correo'        => 'required|email|max:100',
            'contrasena'    => 'required|string|min:6|max:255',
            'fecha_ingreso' => 'required|date|before_or_equal:today',
            'dias'          => 'required|array|min:1',
            'dias.*.dia'          => 'required|string',
            'dias.*.hora_entrada'=> 'required|date_format:H:i',
            'dias.*.hora_salida' => 'required|date_format:H:i|after:dias.*.hora_entrada',
            'dias.*.dia_descanso'=> 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre1.required'      => 'El primer nombre es obligatorio',
            'nombre1.regex'         => 'El primer nombre solo debe contener letras',
            'nombre2.regex'         => 'El segundo nombre solo debe contener letras',
            'apellido1.required'    => 'El primer apellido es obligatorio',
            'apellido1.regex'       => 'El primer apellido solo debe contener letras',
            'apellido2.regex'       => 'El segundo apellido solo debe contener letras',
            'correo.required'       => 'El correo es obligatorio',
            'correo.email'          => 'El correo no tiene un formato válido',
            'contrasena.required'   => 'La contraseña es obligatoria',
            'contrasena.min'        => 'La contraseña debe tener al menos 6 caracteres',
            'fecha_ingreso.required'=> 'La fecha de ingreso es obligatoria',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura',
            'dias.required'         => 'Debe configurar al menos un día de horario',
            'dias.min'              => 'Debe configurar al menos un día de horario',
            'dias.*.hora_salida.after' => 'La hora de salida debe ser posterior a la de entrada',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('dias', []) as $index => $dia) {
                $esDescanso = filter_var($dia['dia_descanso'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $nombreDia  = $dia['dia'] ?? null;

                if ($esDescanso && !in_array($nombreDia, HorarioSemanalService::DIAS_DESCANSO_POSIBLES, true)) {
                    $validator->errors()->add(
                        "dias.$index.dia_descanso",
                        "El día {$nombreDia} no puede marcarse como descanso. Solo se permite de Lunes a Jueves."
                    );
                }
            }
        });
    }
}