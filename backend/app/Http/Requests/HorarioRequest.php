<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barbero'         => 'required|integer|exists:Barberos,IdBarbero',
            'semana'             => 'required|integer|min:1|max:53',
            'ano'                => 'required|integer|min:2020',
            'dias'               => 'required|array|min:1',
            'dias.*.dia'         => 'required|string',
            'dias.*.hora_entrada'=> 'required|date_format:H:i',
            'dias.*.hora_salida' => 'required|date_format:H:i|after:dias.*.hora_entrada',
            'dias.*.dia_descanso'=> 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id_barbero.required' => 'Debe seleccionar un barbero',
            'id_barbero.exists'   => 'El barbero seleccionado no existe',
            'semana.required'     => 'La semana es obligatoria',
            'ano.required'        => 'El año es obligatorio',
            'dias.required'       => 'Debe configurar al menos un día',
            'dias.min'            => 'Debe configurar al menos un día',
        ];
    }
}
