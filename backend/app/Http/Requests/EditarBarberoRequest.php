<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditarBarberoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtenemos el ID del barbero que se está editando desde la ruta
        $barberoId = $this->route('id'); 

        return [
            'nombre1'       => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'nombre2'       => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'apellido1'     => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            'apellido2'     => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/'],
            // Validamos que sea único, pero ignoramos el ID del barbero actual
            'correo'        => ['required', 'email', 'max:100', Rule::unique('barberos', 'correo')->ignore($barberoId)],
            'fecha_ingreso' => 'required|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre1.required'    => 'El primer nombre es obligatorio',
            'nombre1.regex'       => 'El primer nombre solo debe contener letras',
            'nombre2.regex'       => 'El segundo nombre solo debe contener letras',
            'apellido1.required'  => 'El primer apellido es obligatorio',
            'apellido1.regex'     => 'El primer apellido solo debe contener letras',
            'apellido2.regex'     => 'El segundo apellido solo debe contener letras',
            'correo.required'     => 'El correo electrónico es obligatorio',
            'correo.email'        => 'Ingrese un formato de correo válido',
            'correo.unique'       => 'Este correo electrónico ya está registrado por otro barbero',
            'fecha_ingreso.required'=> 'La fecha de ingreso es obligatoria',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura',
        ];
    }
}
