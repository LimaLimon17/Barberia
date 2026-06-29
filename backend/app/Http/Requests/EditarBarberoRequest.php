<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class EditarBarberoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'nombre1' => 'required|string|max:50',
            'nombre2' => 'nullable|string|max:50',
            'apellido1' => 'required|string|max:50',
            'apellido2' => 'nullable|string|max:50',
            'correo' => 'required|email|max:100',
            'fecha_ingreso' => 'required|date|before_or_equal:today',
        ];
    }
    public function messages(): array
    {
        return [
            'nombre1.required' => 'El primer nombre es obligatorio',
            'apellido1.required' => 'El primer apellido es obligatorio',
            'correo.required' => 'El correo electrónico es obligatorio',
            'correo.email' => 'Ingrese un correo electrónico válido',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser posterior a la fecha actual',
        ];
    }
}
